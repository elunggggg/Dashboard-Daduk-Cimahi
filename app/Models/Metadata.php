<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metadata extends Model
{
    protected $table = 'metadatas';

    protected $fillable = ['jenis_indikator', 'nama', 'modul', 'definisi', 'satuan', 'sumber', 'periode_update'];
}
