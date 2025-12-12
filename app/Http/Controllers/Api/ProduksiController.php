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
    // Validasi wajib
    $validated = $request->validate([
        'jenispo' => 'required|numeric',
    ]);

    $jenispo  = $request->jenispo !== 'null' ? $request->jenispo : null;
    $validasi = $request->validasi !== 'null' ? $request->validasi : null;
    $modelPo  = $request->model_po !== 'null' ? $request->model_po : null;

    // Query utama
    $query = DB::table('produksi_po as p')
        ->join('konveksi_buku_potongan as kb', function($join){
            $join->on('kb.kode_po', '=', 'p.kode_po')
                 ->where('kb.hapus', 0);
        })
        ->leftJoin('master_jenis_po as m', 'm.nama_jenis_po', '=', 'p.nama_po')
        ->where('p.hapus', 0)
        ->whereNotIn('p.nama_po', ['BJF','BJK','BJH']);

    // Filter dinamis
    if ($jenispo) {
        $query->where('m.id_jenis_po', $jenispo);
    }

    if ($validasi) {
        $query->where('p.validasi', $validasi);
    }

    if ($modelPo) {
        $query->where('p.model_po', $modelPo);
    }

    // Pagination
    $perPage = $request->input('per_page', 10);
    $page    = $request->input('page', 1);

    $paginated = $query->orderBy('p.kode_po', 'ASC')
                       ->paginate($perPage, ['p.*'], 'page', $page);

    $rows = $paginated->items();

    // Output DataTables
    $data = [];
    $no = ($request->start ?? 0) + 1;

    foreach ($rows as $p) {

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
            0,
        ];
    }

    return response()->json([
        'draw' => $request->draw,
        'data' => $data,
        'recordsTotal' => $paginated->total(),
        'recordsFiltered' => $paginated->total(),
    ]);
}


}
