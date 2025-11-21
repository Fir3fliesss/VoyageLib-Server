<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Borrowing extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'book_id',
        'borrow_duration_days',
        'borrow_date',
        'return_date',
        'actual_return_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'borrow_duration_days' => 'integer',
        'borrow_date' => 'datetime',
        'return_date' => 'datetime',
        'actual_return_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function isOverdue()
    {
        return $this->status === 'dipinjam' && now()->greaterThan($this->return_date);
    }

    public function markAsReturned()
    {
        $this->status = 'dikembalikan';
        $this->actual_return_date = now();
        $this->save();

        // Tambah stok buku
        $this->book->increment('stock');
    }
}
