<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "Running migrate:fresh...\n";

$kernel->call('migrate:fresh', [
    '--force' => true,
]);

echo $kernel->output();
