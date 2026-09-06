<?php

namespace App\Http\Controllers;

use App\Models\Metadata;
use Illuminate\View\View;

class MetadataController extends Controller
{
    public function __invoke(): View
    {
        $byModul = Metadata::query()
            ->orderBy('nama')
            ->get()
            ->mapWithKeys(fn (Metadata $m) => [$m->jenis_indikator => [
                'kode'     => $m->jenis_indikator,
                'nama'     => $m->nama,
                'definisi' => $m->definisi,
                'satuan'   => $m->satuan,
                'sumber'   => $m->sumber,
                'periode'  => $m->periode_update,
                'modul'    => $m->modul,
            ]])
            ->groupBy('modul');

        return view('metadata.index', compact('byModul'));
    }
}
