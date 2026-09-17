<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::findOrFail((int) $argv[1]);
$deadline = microtime(true) + 15;
while (!file_exists($argv[4]) && microtime(true) < $deadline) usleep(10000);
try {
    if ($argv[3] === 'collect') {
        app(App\Services\ReservationLifecycle::class)->collect(App\Models\Reservation::findOrFail((int)$argv[2]), $user);
        echo 'COLLECTED';
        exit(0);
    }
    if ($argv[3] === 'sync') {
        app(App\Services\ReservationLifecycle::class)->sync();
        echo 'SYNCED';
        exit(0);
    }
    app(App\Services\BookingService::class)->book($user, (int) $argv[2], Carbon\Carbon::parse($argv[3]), 5);
    echo 'BOOKED';
} catch (Illuminate\Validation\ValidationException $e) {
    echo 'REJECTED';
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()); exit(2);
}
