<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportExcel extends Model
{
    protected $table = 'import_excels';

    public const STATUS_MENUNGGU = 'menunggu';
    public const STATUS_PRATINJAU = 'pratinjau';
    public const STATUS_BERHASIL = 'berhasil';
    public const STATUS_GAGAL = 'gagal';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const MODE_DKB = 'dkb';
    public const MODE_TEMPLATE = 'template';

    protected $fillable = [
        'user_id', 'nama_file', 'path_file', 'mode', 'nama_profil',
        'tahun', 'semester', 'status', 'jumlah_baris', 'pesan_error', 'catatan_hasil',
        'data_dihapus_pada', 'dihapus_oleh', 'jumlah_baris_dihapus',
    ];

    protected $casts = [
        'data_dihapus_pada' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penghapus(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dihapus_oleh');
    }

    public function dataAgregat(): HasMany
    {
        return $this->hasMany(DataAgregat::class, 'import_id');
    }

    public function bisaDikonfirmasi(): bool
    {
        return $this->status === self::STATUS_PRATINJAU;
    }

    public function bisaHapusData(): bool
    {
        return $this->status === self::STATUS_BERHASIL
            && $this->data_dihapus_pada === null
            && $this->dataAgregat()->exists();
    }

    public function labelStatus(): string
    {
        return match ($this->status) {
            self::STATUS_MENUNGGU => 'Menunggu',
            self::STATUS_PRATINJAU => 'Pratinjau',
            self::STATUS_BERHASIL => 'Berhasil',
            self::STATUS_GAGAL => 'Gagal',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
            default => $this->status,
        };
    }
}
