<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Transactions:\n";
print_r(Illuminate\Support\Facades\Schema::getColumnListing('transactions'));

echo "\nHargaCarts:\n";
print_r(Illuminate\Support\Facades\Schema::getColumnListing('harga_carts'));
