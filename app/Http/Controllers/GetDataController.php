<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GetDataController extends Controller
{

    // saya comment function ini karena ini sudah ada dari BookController 

    // public function getBookByUser(Request $request)
    // {
    //     // Pastikan user sudah login
    //     $user = Auth::guard('sanctum')->user();
    //     if (!$user) {
    //         return response()->json(['message' => 'Unauthenticated'], 401);
    //     }

    //     $keyword = $request->input('keyword');

    //     // Query buku berdasarkan user yang sedang login
    //     $query = Book::with(['user', 'category'])->where('id_user', $user->id);

    //     if ($keyword) {
    //         $query->where('title', 'like', "%{$keyword}%")
    //             ->orWhere('content', 'like', "%{$keyword}%")
    //             ->orWhereHas('category', function (Builder $q) use ($keyword) {
    //                 $q->where('name', 'like', "%{$keyword}%");
    //             });
    //     }

    //     $books = $query->paginate(12);

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
    //             'created_at' => $book->created_at->toIso8601String(),
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
    
    // public function getBookByCategory(Request $request)
    // {
        
    //     // Query semua buku dengan relasi user dan category
    //     $query = Book::with(['user', 'category'])
    //         ->orderBy('id_category') // Mengurutkan berdasarkan ID kategori
    //         ->orderBy('title'); // Mengurutkan judul dalam setiap kategori
    //     $books = $query->get();

    //     // Kelompokkan buku berdasarkan kategori
    //     $groupedByCategory = $books->groupBy(function ($book) {
    //         return $book->category ? $book->category->id : 'Unknown';
    //     });

    //     // Format data untuk respon JSON
    //     $formattedBooks = $groupedByCategory->map(function ($books, $categoryId) {
    //         $categoryName = $books->first()->category->name ?? 'Unknown Category';

    //         return [
    //             'category_id' => $categoryId,
    //             'category_name' => $categoryName,
    //             'books' => $books->map(function ($book) {
    //                 $imagePaths = is_string($book->image) ? json_decode($book->image, true) : $book->image;
    //                 $imagePaths = $imagePaths ?? [];

    //                 return [
    //                     'id' => $book->id,
    //                     'images' => array_map(function ($images, $key) {
    //                         return [
    //                             'id' => is_array($images) && isset($images['id']) ? $images['id'] : $key + 1,
    //                             'url' => is_array($images) && isset($images['url']) 
    //                                 ? $images['url'] 
    //                                 : (is_string($images) ? $images : ''),
    //                         ];
    //                     }, $imagePaths, array_keys($imagePaths)),
    //                     'title' => $book->title,
    //                     'username' => $book->user ? $book->user->username : null,
    //                     'avatar' => $book->user->avatar_image ?? null,
    //                     'category' => $book->category ? $book->category->name : null,
    //                     'content' => $book->content,
    //                    'created_at' => $book->created_at->toIso8601String(), 
    //                     'is_bookmarked' => $this->checkBookmarkStatus($book->id)
    //                 ];
    //             })->values(),
    //         ];
    //     })->values();

    //     // Return hasil JSON
    //     return response()->json([
    //         'data' => $formattedBooks,
    //         'total_books' => $books->count(),
    //    ]);    
    // }
}
