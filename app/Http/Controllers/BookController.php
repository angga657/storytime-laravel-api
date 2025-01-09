<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $keyword = $request->input('keyword');

        $query = Book::with(['user', 'category']);

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

        $books = $query->paginate(10);

        $formattedBooks = $books->map(function ($book) {
            // Pastikan hanya decode jika $book->image adalah string
            $imagePaths = is_string($book->image) ? json_decode($book->image, true) : $book->image;
        
            $imagePaths = $imagePaths ?? []; // Pastikan tetap array jika null
        
            return [
                'id' => $book->id,
                'images' => array_map(fn($image, $key) => [
                    'id' => $image['id'] ?? $key + 1,
                    'url' => $image['url'] ?? (is_string($image) ? $image : ''),
                ], $imagePaths, array_keys($imagePaths)),
                'title' => $book->title,
                'username' => $book->user->username ?? null,
                'category' => $book->category->name ?? null,
                'content' => $book->content,
            ];
        });

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
        // $request->validate([
        //     'title' => 'required|string',
        //     'image.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        //     'id_user' => 'required|exists:users,id',
        //     'id_category' => 'required|exists:categories,id',
        //     'content' => 'required|string',
        // ], [
        //     'title.required' => 'Title must be included.',
        //     'image.*.required' => 'Image must be included.',
        //     'id_user.exists' => 'User must be valid.',
        //     'id_category.exists' => 'Category must be valid.',
        //     'image.*.image' => 'The file must be an image.',
        //     'image.*.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif.',
        //     'image.*.max' => 'The image size must not exceed 2MB.',
        // ]);
    
        // $imageObjects = [];
        // if ($request->hasFile('image')) {
        //     foreach ($request->file('image') as $key => $image) {
        //         $path = $image->store('books', 'public'); // Simpan gambar
        //         $imageObjects[] = [
        //             'id' => $key + 1, // Anda bisa menggunakan metode unik lainnya untuk ID, seperti UUID
        //             'url' => asset('storage/' . $path)
        //         ];
        //     }
        // }

        // // Simpan data ke database
        // $requestData = $request->all();
        // $requestData['image'] = json_encode($imageObjects); // Simpan sebagai JSON 

        // Book::create($requestData);
    
        // return response()->json(['message' => 'Book berhasil disimpan'], 201);
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
        $books = Book::with(['user','category'])->findOrFail($id);

        if(!$books) {
            return response()->json(['message'=>'Book not Found'], 404);
        }

        return response()->json([
            'id' => $books->id,
            'image' => $books->image,
            'title' => $books->title,
            'username' => $books->user ? $books->user->username : null, 
            'category' => $books->category ? $books->category->name : null,
            'content' => $books->content,
        ]);
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

    public function booksByCategory()
    {
        $categories = \App\Models\Category::with(['books.user'])
            ->get()
            ->map(function ($category) {
                return [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'stories' => $category->books->map(function ($book) {
                        return [
                            'story_id' => $book->id,
                            'title' => $book->title,
                            'author' => $book->user ? $book->user->username : 'Unknown',
                            'content' => $book->content,
                            'created_at' => $book->created_at->toISOString(),
                        ];
                    }),
                ];
            });

        return response()->json(['data' => $categories]);
    }
}
