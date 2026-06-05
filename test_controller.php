<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = new Illuminate\Http\Request();
$request->merge(['tahun' => '2025']);

$controller = app(\App\Http\Controllers\Api\ProduksiController::class);
$service = app(\App\Services\ProduksiPoService::class);
$response = $controller->proses_produksi($request, $service);

echo substr(json_encode($response->getData()), 0, 500);
