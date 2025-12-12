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
    $jenispo  = $request->input('jenispo') !== 'null' ? $request->input('jenispo') : null;
    $validasi = $request->input('validasi') !== 'null' ? $request->input('validasi') : null;
    $modelPo  = $request->input('model_po') !== 'null' ? $request->input('model_po') : null;

    $perPage = $request->input('per_page', 25);
    $page    = $request->input('page', 1);

    $query = DB::table('produksi_po as p')
        ->join('konveksi_buku_potongan as kb', function($join){
            $join->on('kb.kode_po', '=', 'p.kode_po')->where('kb.hapus', 0);
        })
        ->leftJoin('master_jenis_po as m', 'm.nama_jenis_po', '=', 'p.nama_po')
        ->where('p.hapus', 0)
        ->whereNotIn('p.nama_po', ['BJF','BJK','BJH']);

    if ($jenispo)  $query->where('m.id_jenis_po', $jenispo);
    if ($validasi) $query->where('p.validasi', $validasi);
    if ($modelPo)  $query->where('p.model_po', $modelPo);

    $paginated = $query->select(
        'p.kode_po',
        DB::raw('MAX(p.keterangan) as keterangan'),
        DB::raw('MAX(p.validasi) as validasi'),
        DB::raw('MAX(p.model_po) as model_po')
    )
    ->groupBy('p.kode_po')
    ->orderBy('p.kode_po','ASC')
    ->paginate($perPage, ['*'], 'page', $page);


    $rows = $paginated->items();
    $data = [];
    $no = ($request->input('start', 0)) + 1;

    foreach($rows as $p){
        $data[] = [
            $no++,
            strtoupper($p->keterangan ?? $p->kode_po),
            $service->getPcs($p->kode_po, 1),
            $service->getPcs($p->kode_po, 2),
            $service->getPcsK($p->kode_po,"SABLON","KIRIM"),
            $service->getPcsK($p->kode_po,"BORDIR","KIRIM"),
            $service->getPcsK($p->kode_po,"JAHIT","KIRIM"),
            $service->getPcsK($p->kode_po,"JAHIT","SETOR"),
            $service->dashKirimGudangPcs($p->kode_po),
            $service->pcsRijek($p->kode_po),
            0
        ];
    }

    return response()->json([
        "draw" => $request->input('draw', 1),
        "recordsTotal" => $paginated->total(),
        "recordsFiltered" => $paginated->total(),
        "data" => $data
    ]);
}



}
