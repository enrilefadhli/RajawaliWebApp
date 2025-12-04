<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (App\Models\PurchaseOrder::select('id','attachment_path')->get() as $po) {
    echo $po->id . " => " . $po->attachment_path . PHP_EOL;
}
