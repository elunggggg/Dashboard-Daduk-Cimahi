<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Petugas\PenggunaRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $pengguna = User::query()
            ->when($q, fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('petugas.pengguna.index', compact('pengguna', 'q'));
    }

    public function create(): View
    {
        return view('petugas.pengguna.create');
    }

    public function store(PenggunaRequest $request): RedirectResponse
    {
        $pengguna = User::create([
            ...$request->safe()->only(['name', 'email', 'password']),
            'role' => User::ROLE_PETUGAS,
        ]);

        $this->audit->created($pengguna);

        return redirect()
            ->route('petugas.pengguna.index')
            ->with('success', "Pengguna {$pengguna->name} berhasil ditambahkan.");
    }

    public function edit(User $pengguna): View
    {
        return view('petugas.pengguna.edit', compact('pengguna'));
    }

    public function update(PenggunaRequest $request, User $pengguna): RedirectResponse
    {
        $data    = $request->safe()->only(['name', 'email']);
        $sebelum = $pengguna->getAttributes();

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $pengguna->fill($data);
        $pengguna->save();

        $this->audit->updated($pengguna, $sebelum);

        return redirect()
            ->route('petugas.pengguna.index')
            ->with('success', "Pengguna {$pengguna->name} berhasil diperbarui.");
    }

    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        if ($pengguna->is($request->user())) {
            return redirect()
                ->route('petugas.pengguna.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Selalu sisakan minimal satu Petugas agar sistem tetap dapat dikelola.
        if ($pengguna->isPetugas() && User::where('role', User::ROLE_PETUGAS)->count() <= 1) {
            return redirect()
                ->route('petugas.pengguna.index')
                ->with('error', 'Tidak dapat menghapus akun Petugas terakhir.');
        }

        $this->audit->deleted($pengguna);
        $nama = $pengguna->name;
        $pengguna->delete();

        return redirect()
            ->route('petugas.pengguna.index')
            ->with('success', "Pengguna {$nama} berhasil dihapus.");
    }
}
