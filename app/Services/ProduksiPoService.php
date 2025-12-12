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
            ->where('fkg.kode_po', $kodepo)
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
            $query->where('rpo.kode_po', $kodePo);
        }

        return (int) $query->sum('rpo.pcs');
    }

    public function getPcs($kodePo, $table)
    {
        if ($table == 1) {
            return DB::table('konveksi_buku_potongan as kb')
                ->join('produksi_po as p', 'p.id_produksi_po', '=', 'kb.idpo')
                ->where('p.kode_po', $kodePo)
                ->where('kb.hapus', 0)
                ->sum('kb.hasil_pieces_potongan');
        }

        if ($table == 2) {
            return DB::table('kelolapo_pengecekan_potongan')
                ->where('kode_po', $kodePo)
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
            ->where('kks.kode_po', $kodePo)
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
            ->where('kode_po', 'LIKE', "%$kodePo%")
            ->sum('rincian_bangke');

        // Hitung pcs valid → pcs - bangke
        return max(0, (int)$totalPcs - (int)$totalBangke);
    }


}
