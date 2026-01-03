<?php

namespace App\Exports;

use App\Services\ReportPotonganService;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithEvents;

class ReportPotonganExport implements
    FromCollection,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithEvents
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
            $this->no++,
            \Carbon\Carbon::parse($row->created_date)->format('d/m/Y'),
            $this->namaTimPotong($row->tim_potong_potongan),
            $row->kode_po,
            $row->pemakaian_bahan_utama ?? '-',
            $row->panjang_gelaran_potongan_utama . ' + ' . $row->panjang_gelaran_variasi,
            $row->jumlah_pemakaian_bahan_utama,
            $row->jumlah_pemakaian_bahan_variasi,
            $row->size_potongan,
            $row->hasil_lusinan_potongan,
            $row->hasil_pieces_potongan,
            '', // Paraf Pimpinan
            '', // Keterangan
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
            'Paraf Pimpinan',
            'Keterangan',
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();

                // ===============================
                // HEADER
                // ===============================
                $sheet->insertNewRowBefore(1, 4);

                $sheet->mergeCells('A1:M1');
                $sheet->mergeCells('A2:M2');
                $sheet->mergeCells('A3:M3');

                $sheet->setCellValue('A1', 'Monitoring Gambar dan Potongan Bahan (MGPB)');
                $sheet->setCellValue('A2', 'PO Produksi Forboys');
                // $sheet->setCellValue('A3', 'Periode : 07 December 2025 - 13 December 2025');
                $from = $this->request->tanggal_from;
                $to   = $this->request->tanggal_to;

                $start = $this->formatTanggal($from);
                $end   = $this->formatTanggal($to);

                $sheet->setCellValue(
                    'A3',
                    "Periode : {$start} - {$end}"
                );

                $sheet->getStyle('A1:A3')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

                // ===============================
                // BORDER TABLE
                // ===============================
                $highestRow = $sheet->getHighestRow();

                $sheet->getStyle("A5:M{$highestRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // ===============================
                // TOTAL
                // ===============================
                $totalRow = $highestRow + 1;

                $sheet->mergeCells("A{$totalRow}:I{$totalRow}");
                $sheet->setCellValue("A{$totalRow}", 'TOTAL');

                $sheet->setCellValue("J{$totalRow}", "=SUM(J6:J{$highestRow})");
                $sheet->setCellValue("K{$totalRow}", "=SUM(K6:K{$highestRow})");

                $sheet->getStyle("A{$totalRow}:M{$totalRow}")
                    ->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('9FE8E0');

                $sheet->getStyle("A{$totalRow}:M{$totalRow}")
                    ->getFont()->setBold(true);

                // ===============================
                // TANDA TANGAN
                // ===============================
                $signRow = $totalRow + 3;

                $sheet->mergeCells("A{$signRow}:C{$signRow}");
                $sheet->mergeCells("K{$signRow}:M{$signRow}");

                $sheet->setCellValue("A{$signRow}", 'Yang Mengecek');
                $sheet->setCellValue("K{$signRow}", 'Yang Membuat');

                $sheet->mergeCells("A".($signRow+4).":C".($signRow+4));
                $sheet->mergeCells("K".($signRow+4).":M".($signRow+4));

                $sheet->setCellValue("A".($signRow+4), 'Agus');
                $sheet->setCellValue("K".($signRow+4), 'Najwa');

                $sheet->getStyle("A{$signRow}:M{$signRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ===============================
                // FOOTER
                // ===============================
                $footerRow = $signRow + 6;

                $sheet->mergeCells("A{$footerRow}:M{$footerRow}");
                $sheet->setCellValue(
                    "A{$footerRow}",
                    'Registered by Forboys Production System ' . now()->format('d-m-Y H:i:s')
                );

                $sheet->getStyle("A{$footerRow}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("A{$footerRow}")
                    ->getFont()->setItalic(true)->setSize(9);
            },
        ];
    }

    private function formatTanggal($date)
    {
        return \Carbon\Carbon::parse($date)->translatedFormat('d F Y');
    }

}
