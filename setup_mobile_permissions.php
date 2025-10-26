<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Spatie\Permission\Models\Permission;
use App\Models\User;

echo "=== Настройка прав для мобильного приложения ===\n\n";

// Список необходимых прав
$permissions = [
    'mobile-app-can-create-team:site',
    'timesheet-view:site',
    'timesheet-add-team:site',
    'timesheet-delete-team:site',
    'timesheet-add-contract:site',
    'timesheet-delete-contract:site',
    'timesheet-add-comment:site',
    'timesheet-delete-comment:site',
];

// Создаем права если их нет
echo "1. Создание прав...\n";
foreach ($permissions as $permName) {
    $perm = Permission::firstOrCreate(
        ['name' => $permName, 'guard_name' => 'site']
    );
    echo "   ✓ $permName\n";
}

echo "\n2. Назначение прав пользователю deathdrumer@yandex.ru...\n";
$user = User::where('email', 'deathdrumer@yandex.ru')->first();

if ($user) {
    foreach ($permissions as $permName) {
        if (!$user->hasPermissionTo($permName)) {
            $user->givePermissionTo($permName);
            echo "   ✓ Добавлено: $permName\n";
        } else {
            echo "   - Уже есть: $permName\n";
        }
    }

    echo "\n3. Проверка прав пользователя:\n";
    $userPerms = $user->getAllPermissions()->pluck('name')->toArray();
    echo "   Всего прав: " . count($userPerms) . "\n";
    echo "   Список:\n";
    foreach ($userPerms as $p) {
        echo "   - $p\n";
    }
} else {
    echo "   ✗ Пользователь не найден!\n";
}

echo "\n=== Готово ===\n";
