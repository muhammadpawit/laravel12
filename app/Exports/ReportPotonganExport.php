<?php

namespace App\Exports;

use App\Services\ReportPotonganService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReportPotonganExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected $service;
    protected $request;

    public function __construct(ReportPotonganService $service, $request)
    {
        $this->service = $service;
        $this->request = $request;
    }

    public function collection()
    {
        $response = $this->service->potongan($this->request);

        // ambil hanya array data
        return collect($response['data']);
    }

    public function map($row): array
    {
        return [
            $row[0],   // #
            $row[1],   // Tanggal
            $row[2],   // Tim Potong
            $row[3],   // Nama PO
            $row[4],   // Roll Bahan
            $row[5],   // Panjang Gelaran
            $row[6],   // Pemakaian Bahan Kaos
            $row[7],   // Pemakaian Bahan Celana
            $row[8],   // Size
            $row[9],   // Jml PO (Dz)
            $row[10],  // Jml PO (Pcs)
        ];
    }

    public function headings(): array
    {
        return [
            'No',
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
}
