<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'id_user',
        'id_category',
        'content',
        'image',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'id_category');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, 'id_book');
    }

    public function getImageAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($book) {
            // Hapus semua bookmark terkait sebelum buku dihapus
            $book->bookmarks()->delete();
        });
    }
}
