<?php

namespace App\Console\Commands;

use App\Services\Sync\RickAndMortySyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncRickAndMortyCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     */
    protected $signature = 'rickandmorty:sync';

    /**
     * La descripción del comando.
     */
    protected $description = 'Sincroniza datos desde la API externa de Rick and Morty hacia la base de datos local de forma idempotente.';

    public function __construct(private readonly RickAndMortySyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Iniciando sincronización con Rick and Morty API...');
        $startTime = microtime(true);

        try {
            $this->syncService->syncAll(function (string $resource, int $currentPage, int $totalPages) {
                $this->output->write("\rSincronizando {$resource}... Página {$currentPage}/{$totalPages}");
                if ($currentPage === $totalPages) {
                    $this->output->writeln('');
                }
            });

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("¡Sincronización completada exitosamente en {$duration} segundos!");

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error("Ocurrió un error fatal durante la sincronización: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}