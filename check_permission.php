<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'deathdrumer@yandex.ru')->first();

if ($user) {
    echo "User found: " . $user->email . "\n";
    echo "Has permission 'mobile-app-can-create-team:site': ";
    echo $user->hasPermissionTo('mobile-app-can-create-team:site') ? 'YES' : 'NO';
    echo "\n";
} else {
    echo "User not found\n";
}
