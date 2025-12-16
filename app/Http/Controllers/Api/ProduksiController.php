<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProduksiPoService;
use App\Services\ReportPotonganService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProduksiController extends Controller
{
    //

    public function proses_produksi(Request $request, ProduksiPoService $service)
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
            'p.id_produksi_po',
            DB::raw('MAX(p.keterangan) as keterangan'),
            DB::raw('MAX(p.validasi) as validasi'),
            DB::raw('MAX(p.model_po) as model_po')
        )
        ->groupBy('p.id_produksi_po', 'p.kode_po')
        ->orderBy('p.id_produksi_po','ASC')
        ->paginate($perPage, ['*'], 'page', $page);


        $rows = $paginated->items();
        $data = [];
        $no = ($request->input('start', 0)) + 1;

        foreach($rows as $p){
            $data[] = [
                $no++,
                strtoupper($p->kode_po),
                $service->getPcs($p->id_produksi_po, 1), // potongan
                // $service->getPcs($p->id_produksi_po, 2),
                $service->getPcsK($p->id_produksi_po,"SABLON","KIRIM"),
                $service->getPcsK($p->id_produksi_po,"BORDIR","KIRIM"),
                $service->getPcsK($p->id_produksi_po,"JAHIT","KIRIM"),
                $service->getPcsK($p->id_produksi_po,"JAHIT","SETOR"),
                $service->dashKirimGudangPcs($p->id_produksi_po),
                $service->pcsRijek($p->id_produksi_po),
                // 0
            ];
        }

        return response()->json([
            "draw" => $request->input('draw', 1),
            "recordsTotal" => $paginated->total(),
            "recordsFiltered" => $paginated->total(),
            "data" => $data
        ]);
    }


    public function potongan(Request $request, ReportPotonganService $service)
{
    // Ambil filter dari request
    $tim      = $request->input('tim');
    $jenispo  = $request->input('jenispo') !== 'null' ? $request->input('jenispo') : null;

    $tanggalFrom = $request->filled('tanggal_from')
        ? Carbon::parse($request->tanggal_from)->startOfDay()
        : null;

    $tanggalTo = $request->filled('tanggal_to')
        ? Carbon::parse($request->tanggal_to)->endOfDay()
        : null;

    $perPage = (int) $request->input('per_page', 25);
    $page    = (int) $request->input('page', 1);

    // Query utama
    $query = DB::table('konveksi_buku_potongan as kbp')
        ->select(
            'kbp.*',
            'mjp.nama_jenis_po as nama_po',
            'p.id_produksi_po as idpo',
            'p.kode_po as kodepo'
        )
        ->join('produksi_po as p', 'p.id_produksi_po', '=', 'kbp.idpo')
        ->join('master_jenis_po as mjp', 'mjp.nama_jenis_po', '=', 'p.nama_po')
        ->where('kbp.hapus', 0)
        ->whereNotLike('mjp.nama_jenis_po', 'BJF%')
        ->whereNotLike('mjp.nama_jenis_po', 'BJK%');

    // Filter tim
    if (!empty($tim) && $tim !== '*') {
        $query->where('kbp.tim_potong_potongan', $tim);
    }

    // Filter jenis PO
    if (!empty($jenispo) && $jenispo !== '*') {
        $query->where('kbp.kode_po', 'like', $jenispo . '%');
    }

    // Filter tanggal (VERSI AMAN)
    if ($tanggalFrom && $tanggalTo) {
        $query->whereBetween('kbp.created_date', [$tanggalFrom, $tanggalTo]);
    } elseif ($tanggalFrom) {
        $query->where('kbp.created_date', '>=', $tanggalFrom);
    } elseif ($tanggalTo) {
        $query->where('kbp.created_date', '<=', $tanggalTo);
    }

    // Order
    $query->orderBy('kbp.created_date', 'asc')
          ->orderBy('kbp.kode_po', 'asc');

    // Pagination
    $paginated = $query->paginate($perPage, ['*'], 'page', $page);
    $rows = $paginated->items();

    // Siapkan data untuk DataTable
    $data = [];
    $no = ($perPage * ($page - 1)) + 1;

    foreach ($rows as $p) {
        $data[] = [
            $no++,
            Carbon::parse($p->created_date)->format('d-m-Y'),
            $this->namaTimPotong($p->tim_potong_potongan),
            strtoupper($p->kodepo),
            $service->getsumroll($p->kodepo, 'UTAMA'),
            $p->panjang_gelaran_potongan_utama . ' + ' . $p->panjang_gelaran_variasi,
            $p->pemakaian_bahan_utama,
            $p->jumlah_pemakaian_bahan_variasi,
            $p->size_potongan,
            $p->hasil_lusinan_potongan,
            $p->hasil_pieces_potongan,
            0,
            0
        ];
    }

    return response()->json([
        'draw' => (int) $request->input('draw', 1),
        'recordsTotal' => $paginated->total(),
        'recordsFiltered' => $paginated->total(),
        'data' => $data
    ]);
}

    public function namaTimPotong($id)
    {
        $roll = DB::table('timpotong')
            ->where('id', $id)
            ->where('hapus', 0)
            ->first();

        return $roll->nama ?? '';
    }




}
