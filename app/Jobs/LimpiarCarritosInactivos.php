<?php
// app/Jobs/LimpiarCarritosInactivos.php

namespace App\Jobs;

use App\Services\CarritoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LimpiarCarritosInactivos implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(CarritoService $service): void
    {
        $service->limpiarInactivos(120); // 2 horas
    }
}