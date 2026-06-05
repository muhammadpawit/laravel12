<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tahunVal = '20252026';
$query = Illuminate\Support\Facades\DB::table('produksi_po as p')
    ->join('konveksi_buku_potongan as kb', function($join){
        $join->on('kb.kode_po', '=', 'p.kode_po')->where('kb.hapus', 0);
    })
    ->leftJoin('master_jenis_po as m', 'm.nama_jenis_po', '=', 'p.nama_po')
    ->where('p.hapus', 0)
    ->where('p.tahun', $tahunVal)
    ->whereNotIn('p.nama_po', ['BJF','BJK','BJH']);

echo "Count: " . $query->count() . "\n";
