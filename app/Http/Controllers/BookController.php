<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        
        // Get the search keyword from the request
        $keyword = $request->input('keyword');

        // Start the query for books
        $query = Book::with(['user', 'category']);

        // If a keyword is provided, apply the search conditions
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%")
                ->orWhereHas('user', function (Builder $q) use ($keyword) {
                    $q->where('username', 'like', "%{$keyword}%");
                })
                ->orWhereHas('category', function (Builder $q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                })
                ->orWhere('content', 'like', "%{$keyword}%");
        }

        // Paginate the results
        $books = $query->with('user', 'category')
            ->leftJoin('users', 'books.id_user', '=', 'users.id')
            ->leftJoin('categories', 'books.id_category', '=', 'categories.id')
            ->select('books.*')
            ->paginate(10);

        // Format the results
        $formattedBooks = $books->map(function ($book) {
            // Ensure safe conversion of images
            $imagePaths = is_string($book->image) 
                ? json_decode($book->image, true) 
                : ($book->image ?? []);

            return [
                'id' => $book->id,
                'images' => array_map(function ($image, $key) {
                    return [
                        'id' => is_array($image) && isset($image['id']) ? $image['id'] : $key + 1,
                        'url' => is_array($image) && isset($image['url']) 
                            ? $image['url'] 
                            : (is_string($image) ? $image : ''),
                    ];
                }, $imagePaths, array_keys($imagePaths)),
                'title' => $book->title,
                'username' => $book->user ? $book->user->username : null,
                'avatar' => $book->user->avatar_image ?? null,
                'category' => $book->category ? $book->category->name : null,
                'content' => $book->content,
                'created_at' => $book->created_at->format('d-m-Y'),
            ];
        });

        // Return the paginated products as JSON
        return response()->json([
            'data' => $formattedBooks,
            'current_page' => $books->currentPage(),
            'last_page' => $books->lastPage(),
            'per_page' => $books->perPage(),
            'total' => $books->total(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'title' => 'required|string',
            'image.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_category' => 'required|exists:categories,id',
            'content' => 'required|string',
        ], [
            'title.required' => 'Title must be included.',
            'image.*.required' => 'Image must be included.',
            'id_category.exists' => 'Category must be valid.',
            'image.*.image' => 'The file must be an image.',
            'image.*.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
            'image.*.max' => 'The image size must not exceed 2MB.',
        ]);
    
        $imageObjects = [];
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $key => $image) {
                $path = $image->store('books', 'public'); // Simpan gambar
                $imageObjects[] = [
                    'id' => $key + 1, // Anda bisa menggunakan metode unik lainnya untuk ID, seperti UUID
                    'url' => asset('storage/' . $path)
                ];
            }
        }
    
        // Simpan data ke database
        $requestData = $request->all();
        $requestData['id_user'] = Auth::id(); // Tetapkan ID pengguna login
        $requestData['image'] = json_encode($imageObjects); // Simpan sebagai JSON 
    
        Book::create($requestData);
    
        return response()->json(['message' => 'Book berhasil disimpan'], 201);

        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        // try {
        //     // Temukan buku dengan relasi user dan category
        //     $book = Book::with(['user', 'category'])->findOrFail($id);
    
        //     // Decode gambar
        //     $images = is_string($book->image) 
        //         ? json_decode($book->image, true) 
        //         : ($book->image ?? []);
    
        //     return response()->json([
        //             'id' => $book->id,
        //             'title' => $book->title,
        //             'category' => $book->category ? $book->category->name : null,
        //             'content' => $book->content,
        //             'images' => $images,
        //             'username' => $book->user->username ?? 'Unknown User',
        //             'avatar' => $book->user->avatar_image ?? null,
                
        //     ], 200);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'message' => 'Book not found',
        //         'error' => $e->getMessage(),
        //     ], 404);
        // }

        try {
            // Temukan buku dengan relasi user dan category
            $book = Book::with(['user', 'category'])->findOrFail($id);

            // Decode gambar
            $images = is_string($book->image) 
                ? json_decode($book->image, true) 
                : ($book->image ?? []);

            // Dapatkan buku dengan kategori yang sama
            $relatedBooks = Book::with('user')
                ->where('id_category', $book->id_category)
                ->where('id', '!=', $book->id)
                ->limit(5)
                ->get()
                ->map(function ($relatedBook) {
                    return [
                        'id' => $relatedBook->id,
                        'title' => $relatedBook->title,
                        'category' => $relatedBook->category->name ?? 'Unknown Category',
                        'username' => $relatedBook->user->username ?? 'Unknown Author',
                        'avatar' => $relatedBook->user->avatar_image ?? null,
                        'images' => is_string($relatedBook->image) 
                            ? json_decode($relatedBook->image, true) 
                            : ($relatedBook->image ?? []),
                        'created_at' => $relatedBook->created_at->format('d-m-Y'),
                    ];
                });

            return response()->json([
                'id' => $book->id,
                'title' => $book->title,
                'category' => $book->category ? $book->category->name : null,
                'content' => $book->content,
                'images' => $images,
                'username' => $book->user->username ?? 'Unknown User',
                'avatar' => $book->user->avatar_image ?? null,
                'created_at' => $book->created_at->format('d-m-Y'),
                'related_books' => $relatedBooks,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Book not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
         try {
            // Find the book or fail
            $book = Book::findOrFail($id);
    
            // Validasi
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string',
                'id_category' => 'sometimes|required|exists:categories,id',
                'content' => 'sometimes|required|string',
                'image.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                // Tambahkan opsi untuk menghapus gambar
                'remove_images' => 'sometimes|array',
            ]);
    
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Persiapkan data update
            $updateData = [];

            // Handle text fields
            $textFields = ['title', 'id_category', 'content'];
            foreach ($textFields as $field) {
                if ($request->has($field)) {
                    $updateData[$field] = $request->input($field);
                }
            }

            // Decode existing images (safely handle different input types)
            $existingImages = is_string($book->image) 
                ? json_decode($book->image, true) 
                : ($book->image ?? []);

            // Proses penghapusan gambar yang dipilih
            if ($request->has('remove_images')) {
                $removeImageIds = $request->input('remove_images');
                $existingImages = array_filter($existingImages, function($image) use ($removeImageIds) {
                    // Handle different image array structures
                    $imageId = is_array($image) ? ($image['id'] ?? null) : null;
                    return !in_array($imageId, $removeImageIds);
                });
            }

            // Proses upload gambar baru
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('books', 'public');
                        
                        // Cari ID maksimum yang ada saat ini
                        $maxId = 0;
                        foreach ($existingImages as $img) {
                            $imgId = is_array($img) ? ($img['id'] ?? 0) : 0;
                            $maxId = max($maxId, $imgId);
                        }
                        
                        $existingImages[] = [
                            'id' => $maxId + 1, // Tambahkan ID baru berdasarkan nilai maksimum
                            'url' => asset('storage/' . $path),
                        ];
                    }
                }
            }

            // Simpan gambar sebagai JSON
            $updateData['image'] = json_encode($existingImages, JSON_UNESCAPED_SLASHES);

            // Update buku
            $book->update($updateData);

            // Refresh data buku
            $book->refresh();

            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => [
                    'id' => $book->id,
                    'images' => is_string($book->image) 
                        ? json_decode($book->image, true) 
                        : $book->image,
                    'title' => $book->title,
                    'username' => $book->user ? $book->user->username : null, 
                    'category' => $book->category ? $book->category->name : null,
                    'content' => $book->content,
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Kesalahan Update Buku', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Gagal memperbarui buku',
                'error' => $e->getMessage()
            ], 500);
        }
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $book = Book::findOrFail($id);

        $book->delete();
        return response()->json([
            'message' => 'Book berhasil dihapus.',
        ], 200);
    }

    // public function userBooksIndex(Request $request)
    // {
    //     $keyword = $request->input('keyword');

    //     $query = Book::with('user');

    //     if ($keyword) {
    //         $query->where('title', 'like', "%{$keyword}%")
    //             ->orWhereHas('user', function (Builder $q) use ($keyword) {
    //                 $q->where('username', 'like', "%{$keyword}%");
    //             })
    //             ->orWhere('content', 'like', "%{$keyword}%");
    //     }

    //     $books = $query->paginate(10);

    //     $formattedBooks = $books->map(function ($book) {
    //         $imagePaths = is_string($book->image) ? json_decode($book->image, true) : $book->image;
    //         $imagePaths = $imagePaths ?? [];

    //         return [
    //             'book_id' => $book->id,
    //             'title' => $book->title,
    //             'user' => $book->user->username ?? 'Unknown Author',
    //             'avatar_image' => $book->user->avatar_image ?? null,
    //             'content' => $book->content,
    //             'created_at' => $book->created_at->toIso8601String(),
    //             'images' => array_map(fn($image, $key) => [
    //                 'id' => $image['id'] ?? $key + 1,
    //                 'url' => $image['url'] ?? (is_string($image) ? $image : ''),
    //             ], $imagePaths, array_keys($imagePaths)),
    //         ];
    //     });

    //     return response()->json([
    //         'data' => $formattedBooks->values(),
    //         'current_page' => $books->currentPage(),
    //         'last_page' => $books->lastPage(),
    //         'per_page' => $books->perPage(),
    //         'total' => $books->total(),
    //     ]);
    // }

    

    public function getBookByUser(Request $request, $userId)
    {
        $keyword = $request->input('keyword');

        // Query books with relationships and filter by user ID
        $query = Book::with(['user', 'category'])->where('id_user', $userId);

        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%")
                ->orWhere('content', 'like', "%{$keyword}%")
                ->orWhereHas('category', function (Builder $q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
        }

        $books = $query->paginate(10);

        $formattedBooks = $books->map(function ($book) {
            $imagePaths = is_string($book->image) ? json_decode($book->image, true) : $book->image;
            $imagePaths = $imagePaths ?? [];

            return [
                'id' => $book->id,
                'title' => $book->title,
                'username' => $book->user->username ?? 'Unknown Author',
                'avatar_image' => $book->user->avatar_image ?? null,
                'content' => $book->content,
                'created_at' => $book->created_at->toIso8601String(),
                'images' => array_map(fn($image, $key) => [
                    'id' => $image['id'] ?? $key + 1,
                    'url' => $image['url'] ?? (is_string($image) ? $image : ''),
                ], $imagePaths, array_keys($imagePaths)),
            ];
        });

        return response()->json([
            'data' => $formattedBooks->values(),
            'current_page' => $books->currentPage(),
            'last_page' => $books->lastPage(),
            'per_page' => $books->perPage(),
            'total' => $books->total(),
        ]);
    }
    
    public function getBookByCategory(Request $request)
    {
        $keyword = $request->input('keyword');

        // Query semua buku dengan relasi user dan category
        $query = Book::with(['user', 'category'])
            ->when($keyword, function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhereHas('user', function (Builder $q) use ($keyword) {
                        $q->where('username', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('category', function (Builder $q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    })
                    ->orWhere('content', 'like', "%{$keyword}%");
            })
            ->orderBy('id_category') // Mengurutkan berdasarkan ID kategori
            ->orderBy('title'); // Mengurutkan judul dalam setiap kategori

        $books = $query->get();

        // Kelompokkan buku berdasarkan kategori
        $groupedByCategory = $books->groupBy(function ($book) {
            return $book->category ? $book->category->id : 'Unknown';
        });

        // Format data untuk respon JSON
        $formattedBooks = $groupedByCategory->map(function ($books, $categoryId) {
            $categoryName = $books->first()->category->name ?? 'Unknown Category';

            return [
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'books' => $books->map(function ($book) {
                    $imagePaths = is_string($book->image) ? json_decode($book->image, true) : $book->image;
                    $imagePaths = $imagePaths ?? [];

                    return [
                        // 'id' => $book->id,
                        // 'title' => $book->title,
                        // 'user' => $book->user->username ?? 'Unknown Author',
                        // 'avatar_image' => $book->user->avatar_image ?? null,
                        // 'content' => $book->content,
                        // 'created_at' => $book->created_at->format('d-m-Y'),
                        // 'images' => array_map(fn($image, $key) => [
                        //     'id' => $image['id'] ?? $key + 1,
                        //     'url' => $image['url'] ?? (is_string($image) ? $image : ''),
                        // ], $imagePaths, array_keys($imagePaths)),
                        'id' => $book->id,
                        'images' => array_map(function ($image, $key) {
                            return [
                                'id' => is_array($image) && isset($image['id']) ? $image['id'] : $key + 1,
                                'url' => is_array($image) && isset($image['url']) 
                                    ? $image['url'] 
                                    : (is_string($image) ? $image : ''),
                            ];
                        }, $imagePaths, array_keys($imagePaths)),
                        'title' => $book->title,
                        'username' => $book->user ? $book->user->username : null,
                        'avatar' => $book->user->avatar_image ?? null,
                        'category' => $book->category ? $book->category->name : null,
                        'content' => $book->content,
                        'created_at' => $book->created_at->format('d-m-Y'),
                    ];
                })->values(),
            ];
        })->values();

        // Return hasil JSON
        return response()->json([
            'data' => $formattedBooks,
            'total_books' => $books->count(),
        ]);

        
    }

}
