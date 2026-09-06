<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = ['user_id', 'aksi', 'subjek_tipe', 'subjek_id', 'perubahan', 'ip_address'];

    protected $casts = ['perubahan' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
