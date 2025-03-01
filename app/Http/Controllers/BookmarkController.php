<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;

class BookmarkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
        $query = Bookmark::query()
            ->with(['user', 'book'])
            ->where('id_user', Auth::id()); // Filter berdasarkan pengguna yang login

        $bookmarks = $query->paginate(4);

        $formattedBookmarks = $bookmarks->map(function ($bookmark) {
            $book = $bookmark->book;
            $imagePaths = is_string($book->image)
                ? json_decode($book->image, true)
                : ($book->image ?? []);
        
            // Ambil hanya gambar dengan id 1
            // $filteredImages = array_values(array_filter($imagePaths, function ($image) {
            //     return isset($image['id']) && $image['id'] == 1;
            // }));

            $images = is_string($book->image)
                ? json_decode($book->image, true)
                : ($book->image ?? []);
            $selectedImage = count($images) > 0 ? [$images[0]] : [];
        
            return [
                'id' => $book->id,
                'images' => $selectedImage,
                'title' => $book->title ?? null,
                'username' => $bookmark->user->username ?? null,
                'category' => $book->category->name ?? null,
                'content' => strip_tags($book->content ?? ''),
                'created_at' => $book->created_at->toIso8601String(), 
                'book_creator' => $book->user->username ?? null,
                'is_bookmarked' => true, // Karena bookmark baru saja dibuat
            ];
        });
        

        return response()->json([
            'data' => $formattedBookmarks,
            'current_page' => $bookmarks->currentPage(),
            'last_page' => $bookmarks->lastPage(),
            'per_page' => $bookmarks->perPage(),
            'total' => $bookmarks->total(),
        ]);  
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validatedData = $request->validate([
            'id_book' => 'required|exists:books,id',
        ], [
            'id_book.exists' => 'The selected book is invalid.',
        ]);
    
        // Cek apakah sudah ada bookmark dengan id_user dan id_book yang sama
        $existingBookmark = Bookmark::where('id_user', Auth::id())
            ->where('id_book', $validatedData['id_book'])
            ->first();
    
        if ($existingBookmark) {
            return response()->json([
                'message' => 'Buku ini sudah ada di bookmark Anda.',
            ], 409); // 409 Conflict
        }
    
        // Buat bookmark baru
        $bookmark = Bookmark::create([
            'id_user' => Auth::id(),
            'id_book' => $validatedData['id_book'],
        ]);
    
        // Ambil data lengkap untuk respons
        $bookmark = Bookmark::with(['user', 'book'])->find($bookmark->id);
    
        $formattedBookmark = [
            'id' => $bookmark->id,
            'images' => is_string($bookmark->book->image ?? null)
                ? collect(json_decode($bookmark->book->image, true))->first()
                : ($bookmark->book->image ?? null),
            'title' => $bookmark->book ? $bookmark->book->title : null,
            'username' => $bookmark->user ? $bookmark->user->username : null,
            'category' => $bookmark->book && $bookmark->book->category 
                ? $bookmark->book->category->name 
                : null,
            'content' => strip_tags($bookmark->book ? $bookmark->book->content : null),
            // 'content' => $bookmark->book ? $bookmark->book->content : null,
            'created_at' => $bookmark->created_at->format('d-m-Y'),
        ];
    
        return response()->json([
            'message' => 'Bookmark berhasil disimpan',
            'data' => $formattedBookmark,
        ], 201);
    }

    /**
     * Display the specified resource.
     */

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(string $id)
    // {
    //     try {
    //         $bookmark = Bookmark::findOrFail($id);
    //         $bookmark->delete();
    //         return response()->json([
    //             'message' => 'Bookmark berhasil dihapus.',
    //             'is_bookmarked' => false // Menandakan bahwa bookmark sudah tidak aktif
    //         ], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Bookmark Error', [
    //             'message' => $e->getMessage(),
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         return response()->json([
    //             'message' => 'Terjadi kesalahan saat menghapus bookmark.',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    
    public function destroy(Request $request)
    {
        //
        $request->validate([
            'id_book' => 'required|exists:books,id',
        ]);
    
        $bookmark = Bookmark::where('id_user', Auth::id())
            ->where('id_book', $request->id_book)
            ->first();
    
        if (!$bookmark) {
            return response()->json([
                'message' => 'Bookmark tidak ditemukan.',
            ], 404);
        }
    
        $bookmark->delete();
    
        return response()->json([
            'message' => 'Bookmark berhasil dihapus.',
        ], 200);
    }
}
