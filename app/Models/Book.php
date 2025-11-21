<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Book extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'cover_url',
        'author',
        'description',
        'isbn',
        'category',
        'stock',
        'can_read_online',
        'source',
    ];

    protected $casts = [
        'stock' => 'integer',
        'can_read_online' => 'boolean',
    ];

    public function borrowings()
    {
        return $this->hasMany(Borrowing::class);
    }

    public function isAvailable()
    {
        return $this->stock > 0;
    }
}
