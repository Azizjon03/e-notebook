<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database {--keep=14 : Necha kunlik zaxira saqlanadi} {--connection= : Baza ulanishi (standart: asosiy)}';

    protected $description = 'PostgreSQL bazasini pg_dump bilan storage/app/backups ga zaxiralaydi';

    public function handle(): int
    {
        $config = config('database.connections.'.($this->option('connection') ?: config('database.default')));
        if (($config['driver'] ?? null) !== 'pgsql') {
            $this->error('Zaxira faqat PostgreSQL uchun.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir, 0700);
        $file = $dir.'/'.$config['database'].'-'.now()->format('Y-m-d-His').'.dump';

        // Parol buyruq qatorida ko'rinmasligi uchun PGPASSWORD orqali beriladi.
        $result = Process::env(['PGPASSWORD' => (string) $config['password']])
            ->timeout(600)
            ->run([
                'pg_dump', '--format=custom', '--no-owner',
                '--host='.$config['host'], '--port='.$config['port'],
                '--username='.$config['username'], '--file='.$file, $config['database'],
            ]);

        if (! $result->successful()) {
            File::delete($file);
            $this->error('pg_dump xatosi: '.trim($result->errorOutput()));

            return self::FAILURE;
        }

        // Eski zaxiralarni o'chirish.
        $keep = (int) $this->option('keep');
        collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.dump') && $f->getMTime() < now()->subDays($keep)->getTimestamp())
            ->each(fn ($f) => File::delete($f->getPathname()));

        $this->info('Zaxira: '.$file);

        return self::SUCCESS;
    }
}
