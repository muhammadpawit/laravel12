<?php

namespace App\Exports;

use App\Services\ReportPotonganService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReportPotonganExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected $service;
    protected $request;
    protected $no = 1;

    public function __construct(ReportPotonganService $service, $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    public function collection()
    {
        return collect($this->service->potongan($this->request));
    }

    public function map($row): array
    {
        return [
            $this->no++,                         // #
            $row->created_date,                  // Tanggal
            $this->namaTimPotong($row->tim_potong_potongan),           // Tim Potong
            $row->kode_po,                       // Nama PO
            $row->bahan_potongan ?? '-',         // Roll Bahan
            $row->panjang_gelaran_potongan_utama
                .' + '.$row->panjang_gelaran_variasi, // Panjang Gelaran
            $row->jumlah_pemakaian_bahan_utama, // Pemakaian Bahan Kaos
            $row->jumlah_pemakaian_bahan_variasi, // Pemakaian Bahan Celana
            $row->size_potongan,                // Size
            $row->hasil_lusinan_potongan,        // Jml PO (Dz)
            $row->hasil_pieces_potongan,         // Jml PO (Pcs)
        ];
    }

    public function headings(): array
    {
        return [
            '#',
            'Tanggal',
            'Tim Potong',
            'Nama PO',
            'Roll Bahan',
            'Panjang Gelaran',
            'Pemakaian Bahan Kaos',
            'Pemakaian Bahan Celana',
            'Size',
            'Jml PO (Dz)',
            'Jml PO (Pcs)',
        ];
    }

    function namaTimPotong($id)
    {
        $roll = DB::table('timpotong')
            ->where('id', $id)
            ->where('hapus', 0)
            ->first();

        return $roll->nama ?? '';
    }
}
