<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProduksiPoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduksiController extends Controller
{
    //

    public function monitor(Request $request, ProduksiPoService $service)
{
    
     $validated = $request->validate([
        'jenispo' => 'required', // wajib
    ]);
    
    $jenispo = $request->jenispo != 'null' ? $request->jenispo : null;
    $validasi = $request->validasi != 'null' ? $request->validasi : null;
    $modelPo = $request->model_po != 'null' ? $request->model_po : null;

    $query = DB::table('produksi_po as p')
        ->leftJoin('master_jenis_po as m', 'm.nama_jenis_po', '=', 'p.nama_po')
        ->where('p.hapus', 0)
        ->whereIn('p.kode_po', function ($q) {
            $q->select('kode_po')
              ->from('konveksi_buku_potongan')
              ->where('hapus', 0);
        })
        ->whereNotIn('p.nama_po', ['BJF','BJK','BJH']);

    if ($jenispo) {
        $query->where('m.id_jenis_po', $jenispo);
    }

    if ($validasi) {
        $query->where('p.validasi', $validasi);
    }

    if ($modelPo) {
        $query->where('p.model_po', $modelPo);
    }

    $allpo = $query->orderBy('p.kode_po', 'ASC')->get();

    $data = [];
    $no = ($request->start ?? 0) + 1;

    foreach ($allpo as $p) {

        $data[] = [
            $no++,
            strtoupper($p->keterangan ?? $p->kode_po),
            $service->getPcs($p->kode_po, 1),
            $service->getPcs($p->kode_po, 2),
            $service->getPcsK($p->kode_po, "SABLON", "KIRIM"),
            $service->getPcsK($p->kode_po, "BORDIR", "KIRIM"),
            $service->getPcsK($p->kode_po, "JAHIT", "KIRIM"),
            $service->getPcsK($p->kode_po, "JAHIT", "SETOR"),
            $service->dashKirimGudangPcs($p->kode_po),
            $service->pcsRijek($p->kode_po),
            // app('App\Models\ReportModel')->selisih($p->kode_po),
            // app('App\Models\ReportModel')->bangke($p->kode_po),
        ];
    }

    return response()->json([
        'draw' => $request->draw,
        'data' => $data
    ]);
}

}
