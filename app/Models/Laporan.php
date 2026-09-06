<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Laporan extends Model
{
    protected $table = 'laporans';

    protected $fillable = ['user_id', 'judul', 'jenis', 'parameter', 'path_file'];

    protected $casts = ['parameter' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
