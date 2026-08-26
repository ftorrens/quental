<?php

namespace App\Console\Commands;

use App\Services\Sync\RickAndMortySyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncRickAndMortyCommand extends Command
{
    protected $signature = 'rickandmorty:sync';
    protected $description = 'Sincroniza datos desde la API externa hacia la BD local (Versión simplificada).';

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
            $this->info("¡Sincronización completada en {$duration} segundos!");

            return Command::SUCCESS;

        } catch (Throwable $e) {
            $this->error("Error fatal durante la sincronización: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}