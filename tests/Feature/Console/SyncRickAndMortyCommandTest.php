<?php

namespace Tests\Feature\Console;

use App\Services\Sync\RickAndMortySyncService;
use Exception;
use Illuminate\Console\Command;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SyncRickAndMortyCommandTest extends TestCase
{
    private MockInterface $syncServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Instanciamos el Mock del servicio
        $this->syncServiceMock = Mockery::mock(RickAndMortySyncService::class);
        
        // 2. Le decimos a Laravel que inyecte este Mock cuando el comando lo pida
        $this->instance(RickAndMortySyncService::class, $this->syncServiceMock);
    }

    public function test_command_executes_successfully_and_displays_progress(): void
    {
        // Preparamos el mock para que cuando el comando llame a syncAll, 
        $this->syncServiceMock->shouldReceive('syncAll')
            ->once()
            ->andReturnUsing(function ($callback) {
                // Simulamos la respuesta del servicio llamando al callback 
                // para imprimir el progreso en la terminal (ej. "Sincronizando Personajes... Página 1/2")
                $callback('Personajes', 1, 2);
                $callback('Personajes', 2, 2);
            });

        $this->artisan('rickandmorty:sync')
            ->expectsOutput('Iniciando sincronización con Rick and Morty API...')
            ->assertExitCode(Command::SUCCESS); 
    }

    public function test_command_handles_exceptions_gracefully_and_returns_failure(): void
    {
        $errorMessage = 'Conexión rechazada por límite de tasa (Rate Limit)';

        // Preparamos el mock para forzar un fallo catastrófico en el servicio
        $this->syncServiceMock->shouldReceive('syncAll')
            ->once()
            ->andThrow(new Exception($errorMessage));

        $this->artisan('rickandmorty:sync')
            ->expectsOutput('Iniciando sincronización con Rick and Morty API...')
            ->expectsOutput("Ocurrió un error fatal durante la sincronización: {$errorMessage}")
            ->assertExitCode(Command::FAILURE);
    }
}