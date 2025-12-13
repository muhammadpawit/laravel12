<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportPotonganService
{
    /**
     * Ambil total kirim gudang (FInishing)
     */
    

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

        if (!empty($data['tanggal_from']) && !empty($data['tanggal_to'])) {
            $query->whereBetween(DB::raw('DATE(kbp.created_date)'), [$data['tanggal_from'], $data['tanggal_to']]);
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
