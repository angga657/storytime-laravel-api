<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

class GetDataController extends Controller
{
    //
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
            $imagePaths = is_string($book->images) ? json_decode($book->images, true) : $book->images;
            $imagePaths = $imagePaths ?? [];

            return [
                'id' => $book->id,
                'title' => $book->title,
                'username' => $book->user->username ?? 'Unknown Author',
                'avatar_image' => $book->user->avatar_image ?? null,
                'content' => $book->content,
                'created_at' => $book->created_at->toIso8601String(),
                'images' => array_map(fn($images, $key) => [
                    'id' => $images['id'] ?? $key + 1,
                    'url' => $images['url'] ?? (is_string($images) ? $images : ''),
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
                    $imagePaths = is_string($book->images) ? json_decode($book->images, true) : $book->images;
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
