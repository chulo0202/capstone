<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::where('email', 'admin@fams.local')->first();
if ($user) {
    echo $user->email . PHP_EOL;
    echo $user->role . PHP_EOL;
    echo $user->name . PHP_EOL;
} else {
    echo 'missing' . PHP_EOL;
}
