<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    protected $fillable = ['user_id', 'nama_file', 'ukuran_bytes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
