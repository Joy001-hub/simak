<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Step 1: Autoload... ";
require 'vendor/autoload.php';
echo "OK\n";

echo "Step 2: Bootstrap... ";
$app = require 'bootstrap/app.php';
echo "OK\n";

echo "Step 3: Kernel... ";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
echo "OK\n";

echo "Step 4: Bootstrap Kernel... ";
$kernel->bootstrap();
echo "OK\n";

echo "All done!\n";
