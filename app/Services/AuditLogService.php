<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public const AKSI_CREATE = 'CREATE';

    public const AKSI_UPDATE = 'UPDATE';

    public const AKSI_DELETE = 'DELETE';

    public const AKSI_LOGIN = 'LOGIN';

    public const AKSI_LOGOUT = 'LOGOUT';

    public const AKSI_IMPORT = 'IMPORT';

    public const AKSI_EKSPOR = 'EKSPOR';

    public const AKSI_BACKUP = 'BACKUP';

    // Kolom yang tidak boleh pernah masuk ke audit log
    private const REDACTED = ['password', 'remember_token'];

    /**
     * PENTING: kolom asli tabel audit_logs adalah subjek_tipe/subjek_id/
     * perubahan (lihat migrasi create_audit_logs_table), BUKAN tabel/
     * data_lama/data_baru. Versi lama method ini menulis ke nama kolom yang
     * salah — Eloquent membuang atribut yang tidak ada di $fillable secara
     * DIAM-DIAM (tanpa error), jadi setiap entri audit sejak rebuild
     * tersimpan TANPA detail perubahan sama sekali. Signature publik method
     * ini SENGAJA dipertahankan sama (dipanggil dari banyak controller)
     * supaya perbaikan ini tidak menyentuh pemanggilnya.
     */
    public function record(
        string $aksi,
        ?string $tabel = null,
        ?array $dataLama = null,
        ?array $dataBaru = null,
        int|string|null $subjekId = null,
    ): AuditLog {
        $perubahan = null;

        if ($dataLama !== null || $dataBaru !== null) {
            $perubahan = [
                'sebelum' => $dataLama ? $this->sanitize($dataLama) : null,
                'sesudah' => $dataBaru ? $this->sanitize($dataBaru) : null,
            ];
        }

        return AuditLog::create([
            'user_id'     => auth()->id(),
            'aksi'        => $aksi,
            'subjek_tipe' => $tabel,
            'subjek_id'   => $subjekId ?? $dataBaru['id'] ?? $dataLama['id'] ?? null,
            'perubahan'   => $perubahan,
            'ip_address'  => request()->ip(),
        ]);
    }

    public function created(Model $model): AuditLog
    {
        return $this->record(self::AKSI_CREATE, $model->getTable(), null, $model->getAttributes(), $model->getKey());
    }

    /**
     * @param  array<string, mixed>  $sebelum  snapshot atribut sebelum save()
     */
    public function updated(Model $model, array $sebelum): AuditLog
    {
        // Hanya simpan kolom yang benar-benar berubah agar log tetap ringkas.
        // updated_at diabaikan karena selalu berubah di setiap save().
        $normalize = fn (array $a) => array_map(
            fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v),
            array_diff_key($a, array_flip(['updated_at'])),
        );

        $sesudah = $model->getAttributes();
        $berubah = array_keys(array_diff_assoc($normalize($sesudah), $normalize($sebelum)));

        return $this->record(
            self::AKSI_UPDATE,
            $model->getTable(),
            array_intersect_key($sebelum, array_flip($berubah)),
            array_intersect_key($sesudah, array_flip($berubah)),
            $model->getKey(),
        );
    }

    public function deleted(Model $model): AuditLog
    {
        return $this->record(self::AKSI_DELETE, $model->getTable(), $model->getAttributes(), null, $model->getKey());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        foreach (self::REDACTED as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
