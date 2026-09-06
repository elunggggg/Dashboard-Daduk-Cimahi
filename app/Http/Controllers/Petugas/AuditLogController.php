<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'aksi'    => ['nullable', 'string', 'max:50'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'dari'    => ['nullable', 'date'],
            'sampai'  => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $logs = AuditLog::query()
            ->with('user:id,name,email,role')
            ->when($validated['aksi'] ?? null, fn ($q, $aksi) => $q->where('aksi', $aksi))
            ->when($validated['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($validated['dari'] ?? null, fn ($q, $dari) => $q->where('created_at', '>=', $dari.' 00:00:00'))
            ->when($validated['sampai'] ?? null, fn ($q, $sampai) => $q->where('created_at', '<=', $sampai.' 23:59:59'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $aksiList = [
            AuditLogService::AKSI_CREATE,
            AuditLogService::AKSI_UPDATE,
            AuditLogService::AKSI_DELETE,
            AuditLogService::AKSI_LOGIN,
            AuditLogService::AKSI_LOGOUT,
            AuditLogService::AKSI_IMPORT,
            AuditLogService::AKSI_EKSPOR,
            AuditLogService::AKSI_BACKUP,
        ];

        $penggunaList = User::orderBy('name')->get(['id', 'name']);

        return view('petugas.audit.index', [
            'logs'         => $logs,
            'aksiList'     => $aksiList,
            'penggunaList' => $penggunaList,
            'filter'       => $validated,
        ]);
    }
}
