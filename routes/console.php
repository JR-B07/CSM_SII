<?php

use App\Jobs\CalcularNominasJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recalcula la nómina de la quincena en curso todas las noches a las 23:00,
// como respaldo (el check-in ya recalcula en vivo).
Schedule::job(new CalcularNominasJob())
    ->dailyAt('23:00')
    ->name('calcular-nominas')
    ->withoutOverlapping();
