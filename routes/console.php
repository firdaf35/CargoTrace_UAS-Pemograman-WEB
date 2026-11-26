<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('cargotrace:about', function () {
    $this->info('CargoTrace - delay threshold: '.config('cargotrace.delay_threshold_hours').'h');
})->purpose('Show CargoTrace configuration');
