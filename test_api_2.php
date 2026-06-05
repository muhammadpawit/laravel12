<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/api/proses-produksi', 'GET', ['tahun' => 2025]);
$response = $kernel->handle($request);
echo substr($response->getContent(), 0, 500);
