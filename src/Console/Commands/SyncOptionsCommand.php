<?php

namespace NextDeveloper\Options\Console\Commands;

use Illuminate\Console\Command;
use NextDeveloper\Options\Services\OptionsService;

class SyncOptionsCommand extends Command
{
    protected $signature = 'options:sync
                            {--module=* : One or more namespace prefixes to scan (defaults to NextDeveloper and App)}';

    protected $description = 'Scan registered routes and sync their metadata into the options database';

    public function handle(): int
    {
        $modules = $this->option('module');

        if (empty($modules)) {
            $modules = ['NextDeveloper', 'App'];
        }

        $this->info('Scanning routes for: ' . implode(', ', $modules));

        $synced  = 0;
        $skipped = 0;
        $errors  = 0;

        OptionsService::generate($modules, function (string $status, string $method, string $uri, ?string $reason) use (&$synced, &$skipped, &$errors) {
            match ($status) {
                'sync'  => ($this->line("  <info>SYNC</info>  [{$method}] {$uri}") + $synced++),
                'skip'  => ($this->line("  <comment>SKIP</comment>  [{$method}] {$uri} — {$reason}") + $skipped++),
                'error' => ($this->line("  <error>ERR</error>   [{$method}] {$uri} — {$reason}") + $errors++),
                default => null,
            };
        });

        $this->newLine();
        $this->info("Done. Synced: {$synced}  Skipped: {$skipped}  Errors: {$errors}");

        return self::SUCCESS;
    }
}
