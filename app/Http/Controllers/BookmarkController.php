<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $keyword = $request->input('keyword');

        $query = Bookmark::query()
            ->with(['user', 'book']);

        if ($keyword) {
            $query->whereHas('book', function (Builder $q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%");
            })
            ->orWhereHas('user', function (Builder $q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%");
            });
        }

        $bookmarks = $query->paginate(10);

        $formattedBookmarks = $bookmarks->map(function ($bookmark) {
            $book = $bookmark->book;
            $imagePaths = is_string($book->image)
                ? json_decode($book->image, true)
                : ($book->image ?? []);
        
            $selectedImage = collect($imagePaths)->first();
        
            return [
                'id' => $bookmark->id,
                'image' => [
                    'id' => is_array($selectedImage) && isset($selectedImage['id'])
                        ? $selectedImage['id']
                        : null,
                    'url' => is_array($selectedImage) && isset($selectedImage['url'])
                        ? $selectedImage['url']
                        : null,
                ],
                'title' => $book->title ?? null,
                'username' => $bookmark->user->username ?? null,
                'category' => $book->category->name ?? null,
                'content' => $book->content ?? null,
                'created_at' => $bookmark->created_at->format('d-m-Y'),
                'book_creator' => $book->user->username ?? null, // Tambahkan ini
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
        // $validatedData = $request->validate([
        //     'id_book' => 'required|exists:books,id',
        // ], [
        //     'id_book.exists' => 'The selected book is invalid.',
        // ]);
    
        // Bookmark::create([
        //     'id_user' => Auth::id(),
        //     'id_book' => $validatedData['id_book'],
        // ]);
    
        // return response()->json(['message' => 'Bookmark berhasil disimpan'], 201);

        $validatedData = $request->validate([
            'id_book' => 'required|exists:books,id',
        ], [
            'id_book.exists' => 'The selected book is invalid.',
        ]);
    
        // Buat bookmark baru
        $bookmark = Bookmark::create([
            'id_user' => Auth::id(),
            'id_book' => $validatedData['id_book'],
        ]);
    
        // Ambil data lengkap untuk respons
        $bookmark = Bookmark::with(['user', 'book'])->find($bookmark->id);
    
        $formattedBookmark = [
            'id' => $bookmark->id,
            'image' => is_string($bookmark->book->image ?? null)
                ? collect(json_decode($bookmark->book->image, true))->first()
                : ($bookmark->book->image ?? null),
            'title' => $bookmark->book ? $bookmark->book->title : null,
            'username' => $bookmark->user ? $bookmark->user->username : null,
            'category' => $bookmark->book && $bookmark->book->category 
                ? $bookmark->book->category->name 
                : null,
            'content' => $bookmark->book ? $bookmark->book->content : null,
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
    public function show(string $id)
    {
        //
        try {
            $book = Book::findOrFail($id);

            $images = is_string($book->image) 
                ? json_decode($book->image, true) 
                : ($book->image ?? []);

            $selectedImage = collect($images)->first();

            return response()->json([
                'id' => $book->id,
                'title' => $book->title,
                'category' => $book->id_category,
                'content' => $book->content,
                'image' => $selectedImage,
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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $bookmark = Bookmark::findOrFail($id);

        $bookmark->delete();
        return response()->json([
            'message' => 'Bookmark berhasil dihapus.',
        ], 200);
    }
}
