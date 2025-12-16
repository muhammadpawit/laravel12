<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProduksiPoService
{
    /**
     * Ambil total kirim gudang (FInishing)
     */
    public function dashKirimGudangPcs($kodepo)
    {
        return DB::table('finishing_kirim_gudang as fkg')
            ->join('produksi_po as p', 'p.id_produksi_po', '=', 'fkg.idpo')
            ->where('fkg.idpo', $kodepo)
            ->whereNull('tahunpo')
            ->sum('fkg.jumlah_piece_diterima');
    }

    /**
     * Ambil total pcs rijek
     */
    public function pcsRijek($kodePo, $jenis = null)
    {
        $query = DB::table('rijek as rpo')
            ->leftJoin('kelolapo_kirim_setor as kbp', 'kbp.kode_po', '=', 'rpo.kode_po')
            ->leftJoin('produksi_po as p', 'p.id_produksi_po', '=', 'kbp.idpo')
            ->leftJoin('master_jenis_po as mjp', 'mjp.nama_jenis_po', '=', 'p.nama_po')
            ->where('mjp.tampil', 1)
            ->where('kbp.kategori_cmt', 'JAHIT')
            ->where('kbp.progress', 'SETOR')
            ->where('kbp.hapus', 0);

        if ($jenis) {
            $query->where('mjp.idjenis', $jenis);
        }

        if ($kodePo) {
            $query->where('rpo.idpo', $kodePo);
        }

        return (int) $query->sum('rpo.pcs');
    }

    public function getPcs($kodePo, $table)
    {
        if ($table == 1) {
            return DB::table('konveksi_buku_potongan as kb')
                ->join('produksi_po as p', 'p.id_produksi_po', '=', 'kb.idpo')
                ->where('p.id_produksi_po', $kodePo)
                ->where('kb.hapus', 0)
                ->sum('kb.hasil_pieces_potongan');
        }

        if ($table == 2) {
            return DB::table('kelolapo_pengecekan_potongan')
                ->where('id_produksi_po', $kodePo)
                ->value('jumlah_total_potongan') ?? 0;
        }

        return 0;
    }

    public function getPcsK($kodePo, $kategori, $progress)
    {
        // Hitung pcs dengan koreksi jika po_count > 1
        $baseQuery = DB::table('kelolapo_kirim_setor as kks')
            ->join('produksi_po as p', 'p.id_produksi_po', '=', 'kks.idpo')
            ->where('kks.hapus', 0)
            ->where('kks.idpo', $kodePo)
            ->where('kks.kategori_cmt', $kategori)
            ->where('kks.progress', $progress)
            ->where('kks.id_master_cmt', '!=', 85);

        $poCount = (clone $baseQuery)->count();

        $totalPcs = (clone $baseQuery)->sum('kks.qty_tot_pcs');

        // Jika double count → bagi 2
        if ($poCount > 1) {
            $totalPcs *= 0.5;
        }

        // Hitung bangke
        $totalBangke = DB::table('kelolapo_rincian_setor_cmt_finish')
            // ->where('kode_po', 'LIKE', "%$kodePo%")
            ->where('idpo', $kodePo)
            ->sum('rincian_bangke');

        // Hitung pcs valid → pcs - bangke
        return max(0, (int)$totalPcs - (int)$totalBangke);
    }


    public function potongan($data)
    {
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

        if (!empty($data['tim'])) {
            $query->where('kbp.tim_potong_potongan', $data['tim']);
        }

        if (!empty($data['jenis'])) {
            $query->where('kbp.kode_po', 'like', $data['jenis'] . '%');
        }

        if (!empty($data['tanggal1']) && !empty($data['tanggal2'])) {
            $query->whereBetween(DB::raw('DATE(kbp.created_date)'), [$data['tanggal1'], $data['tanggal2']]);
        }

        $query->orderBy(DB::raw('DATE(kbp.created_date)'), 'asc')
            ->orderBy('kbp.kode_po', 'asc');

        return $query->get()->toArray();
    }

    public function getsumroll($kode_po, $kategori)
    {
        $roll = DB::table('gudang_bahan_keluar')
            ->where('kode_po', $kode_po)
            ->where('bahan_kategori', $kategori)
            ->where('hapus', 0)
            ->sum('jumlah_item_keluar');

        return $roll;
    }

    
    



}
