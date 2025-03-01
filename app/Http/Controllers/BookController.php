<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Bookmark;
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

    private function checkBookmarkStatus($bookId)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return false;
        }
        
        return Bookmark::where('id_user', $user->id)
            ->where('id_book', $bookId)
            ->exists();
    }
    public function index(Request $request)
    {
        
        // Get parameters
        $keyword = $request->input('keyword');
        $sort = $request->input('sort', 'newest'); // Default sorting: newest
        $category = $request->input('id_category'); // Ambil category ID dari request

        // Start the query for books
        $query = Book::with(['user', 'category']);

        // Apply search filter
        if ($keyword) {
            $query->where('title', 'like', "%{$keyword}%")
                ->orWhereHas('user', function (Builder $q) use ($keyword) {
                    $q->where('username', 'like', "%{$keyword}%");
                });
        }

        // Apply category filter
        if ($category) {
            $query->where('id_category', $category);
        }

        // Apply sorting
        switch ($sort) {
            case 'popular': // Sort by bookmark count (descending)
                // $query->withCount('bookmarks')->orderBy('bookmarks_count', 'desc');
                $query->withCount('bookmarks')->orderByRaw('COALESCE(bookmarks_count, 0) DESC');
                break;
            case 'a-z': // Sort alphabetically (ascending)
                $query->orderBy('title', 'asc');
                break;
            case 'z-a': // Sort alphabetically (descending)
                $query->orderBy('title', 'desc');
                break;
            case 'newest': // Default: sort by latest creation date
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        // Paginate results
        $books = $query->paginate(12);

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
                'content' => strip_tags($book->content ?? ''),
                'created_at' => $book->created_at->toIso8601String(), 
                'is_bookmarked' => $this->checkBookmarkStatus($book->id)
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
        try {
            $request->validate([
                'title' => 'required|string',
                'image.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'id_category' => 'required|exists:categories,id',
                'content' => 'required|string',
            ], [
                'title.required' => 'Title must be included.',
                'image.*.required' => 'Images must be included.',
                'id_category.exists' => 'Category must be valid.',
                'image.*.image' => 'The file must be an images.',
                'image.*.mimes' => 'The images must be a file of type: jpeg, png, jpg, gif.',
                'image.*.max' => 'The images size must not exceed 2MB.',
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
        } catch (\Exception $e) {
            \Log::error('Gagal menyimpan buku', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'message' => 'Gagal menyimpan buku.',
                'error' => $e->getMessage(),
            ], 500);
        }  
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
                        'content' => $relatedBook->content,
                        'category' => $relatedBook->category->name ?? 'Unknown Category',
                        'username' => $relatedBook->user->username ?? 'Unknown Author',
                        'avatar' => $relatedBook->user->avatar_image ?? null,
                        'images' => is_string($relatedBook->image) 
                            ? json_decode($relatedBook->image, true) 
                            : ($relatedBook->image ?? []),
                        'created_at' => $relatedBook->created_at->toIso8601String(), 
                        'is_bookmarked' => $this->checkBookmarkStatus($relatedBook->id)
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
                'created_at' => $book->created_at->toIso8601String(), 
                'is_bookmarked' => $this->checkBookmarkStatus($book->id),
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

            // Cek apakah user yang sedang login adalah pemilik buku
            if ($book->id_user !== Auth::id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
    
            // Validasi
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string',
                'id_category' => 'sometimes|required|exists:categories,id',
                'content' => 'sometimes|required|string',
                'image.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
                // Tambahkan opsi untuk menghapus gambar
                'remove_images' => 'sometimes|array',
            ], [
                'title.required' => 'Title must be included.',
                'image.*.required' => 'Images must be included.',
                'id_category.exists' => 'Category must be valid.',
                'image.*.image' => 'The file must be an images.',
                'image.*.mimes' => 'The images must be a file of type: jpeg, png, jpg, gif.',
                'image.*.max' => 'The images size must not exceed 2MB.',
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
                $existingImages = array_filter($existingImages, function($images) use ($removeImageIds) {
                    // Ambil ID gambar dari array (dengan penanganan struktur array yang berbeda)
                    $imageId = is_array($images) ? ($images['id'] ?? null) : null;
                    
                    if (in_array($imageId, $removeImageIds)) {
                        $filePath = is_array($images) && isset($images['url']) 
                            ? str_replace(asset('storage/'), '', $images['url']) 
                            : null;
                        if ($filePath && Storage::disk('public')->exists($filePath)) {
                            Storage::disk('public')->delete($filePath);
                        }
                        return false; // Hapus gambar dari array
                    }
                    return true;
                });
                
                // Reset key array agar berurutan
                $existingImages = array_values($existingImages);
            }

            // Proses upload gambar baru
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $images) {
                    if ($images && $images->isValid()) {
                        $path = $images->store('books', 'public');
                        
                        // Cari ID maksimum yang ada saat ini
                        $maxId = 0;
                        foreach ($existingImages as $img) {
                            $imgId = is_array($img) ? ($img['id'] ?? 0) : 0;
                            $maxId = max($maxId, $imgId);
                        }
                        
                        $existingImages[] = [
                            'id' => $maxId + 1, // Tambahkan ID baru
                            'url' => asset('storage/' . $path),
                        ];
                    }
                }
                
                // Reset lagi key array jika diperlukan
                $existingImages = array_values($existingImages);
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
                   'created_at' => $book->created_at->toIso8601String(), 
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
            'message' => 'Buku berhasil dihapus.',
        ], 200);
    }

    // public function getBookByUser(Request $request, $userId)
    // {

    //     // Query books with relationships and filter by user ID
    //     $query = Book::with(['user', 'category'])->where('id_user', $userId);
    //     $books = $query->paginate(4);

    //     $formattedBooks = $books->map(function ($book) {
    //         $imagePaths = is_string($book->image) ? json_decode($book->image, true) : $book->image;
    //         $imagePaths = $imagePaths ?? [];

    //         return [
    //             'id' => $book->id,
    //             'title' => $book->title,
    //             'username' => $book->user->username ?? 'Unknown Author',
    //             'avatar_image' => $book->user->avatar_image ?? null,
    //             'content' => $book->content,
    //             'category' => $book->category ? $book->category->name : null,
    //            'created_at' => $book->created_at->toIso8601String(), 
    //             'images' => array_map(fn($images, $key) => [
    //                 'id' => $images['id'] ?? $key + 1,
    //                 'url' => $images['url'] ?? (is_string($images) ? $images : ''),
    //             ], $imagePaths, array_keys($imagePaths)),
    //             'is_bookmarked' => $this->checkBookmarkStatus($book->id)
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


    public function getBookByUser(Request $request)
    {
        // Pastikan user sudah login
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }


        // Query buku berdasarkan user yang sedang login
        $query = Book::with(['user', 'category'])->where('id_user', $user->id);

        $books = $query->paginate(4);

        $formattedBooks = $books->map(function ($book) {
            $imagePaths = is_string($book->image) ? json_decode($book->image, true) : $book->image;
            $imagePaths = $imagePaths ?? [];

            return [
                'id' => $book->id,
                'title' => $book->title,
                'username' => $book->user->username ?? 'Unknown Author',
                'avatar_image' => $book->user->avatar_image ?? null,
                'content' => $book->content,
                'category' => $book->category ? $book->category->name : null,
                'created_at' => $book->created_at->toIso8601String(),
                'images' => array_map(fn($images, $key) => [
                    'id' => $images['id'] ?? $key + 1,
                    'url' => $images['url'] ?? (is_string($images) ? $images : ''),
                ], $imagePaths, array_keys($imagePaths)),
                'is_bookmarked' => $this->checkBookmarkStatus($book->id)
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
        
        // Query semua buku dengan relasi user dan category
        $query = Book::with(['user', 'category'])
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
                        'id' => $book->id,
                        'images' => array_map(function ($images, $key) {
                            return [
                                'id' => is_array($images) && isset($images['id']) ? $images['id'] : $key + 1,
                                'url' => is_array($images) && isset($images['url']) 
                                    ? $images['url'] 
                                    : (is_string($images) ? $images : ''),
                            ];
                        }, $imagePaths, array_keys($imagePaths)),
                        'title' => $book->title,
                        'username' => $book->user ? $book->user->username : null,
                        'avatar' => $book->user->avatar_image ?? null,
                        'category' => $book->category ? $book->category->name : null,
                        'content' => $book->content,
                       'created_at' => $book->created_at->toIso8601String(), 
                        'is_bookmarked' => $this->checkBookmarkStatus($book->id)
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