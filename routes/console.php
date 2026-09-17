<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('quickwash:sync {--watch : Mantener el reloj activo cada 5 segundos}', function () {
    do {
        app(\App\Services\ReservationLifecycle::class)->sync();
        if (!$this->option('watch')) break;
        sleep(5);
    } while (true);
    $this->info('Reservas sincronizadas.');
})->purpose('Iniciar lavados por horario y habilitar la recogida, sin finalizar automáticamente');
