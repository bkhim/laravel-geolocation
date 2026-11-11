<?php

namespace Bkhim\Geolocation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class UpdateMaxMindCommand extends Command
{
    protected $signature = 'geolocation:update-maxmind
                            {--dry-run : Show what would be downloaded without actually downloading}
                            {--edition=GeoLite2-City : MaxMind edition to download}
                            {--force : Overwrite the existing database without asking}';

    protected $description = 'Download, extract, and install the MaxMind database';

    public function handle(): int
    {
        $licenseKey = config('geolocation.providers.maxmind.license_key');

        if (! $licenseKey) {
            $this->error('MaxMind license key not configured. Set MAXMIND_LICENSE_KEY in .env');

            return self::FAILURE;
        }

        $databasePath = config('geolocation.providers.maxmind.database_path');
        $edition = (string) $this->option('edition');

        if (! $databasePath) {
            $this->error('MaxMind database path not configured.');

            return self::FAILURE;
        }

        // Relative paths are resolved against storage/, matching the driver.
        if (! str_starts_with($databasePath, DIRECTORY_SEPARATOR)) {
            $databasePath = storage_path($databasePath);
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run mode - would download from: https://download.maxmind.com/app/geoip_download');
            $this->line("  edition: {$edition}");
            $this->line("  target:  {$databasePath}");

            return self::SUCCESS;
        }

        $this->info("Downloading {$edition}...");

        $response = Http::withHeaders(['User-Agent' => 'Laravel-Geolocation/1.0'])
            ->timeout(300)
            ->get('https://download.maxmind.com/app/geoip_download', [
                'edition_id' => $edition,
                'license_key' => $licenseKey,
                'suffix' => 'tar.gz',
            ]);

        if ($response->failed()) {
            $this->error('Download failed with HTTP status '.$response->status());

            return self::FAILURE;
        }

        $directory = dirname($databasePath);
        File::ensureDirectoryExists($directory);

        $archive = $directory.'/'.$edition.'.tar.gz';
        File::put($archive, $response->body());
        $this->line("Saved archive: {$archive}");

        $extracted = $this->extract($archive, $directory);

        if ($extracted === null) {
            $this->error('Could not extract the archive. The system "tar" binary is required.');

            return self::FAILURE;
        }

        // Move the extracted .mmdb into place atomically.
        if (File::exists($databasePath) && ! $this->option('force')) {
            if (! $this->confirm("Replace existing database at {$databasePath}?")) {
                $this->warn('Aborted. The downloaded archive was kept.');

                return self::FAILURE;
            }
        }

        File::move($extracted, $databasePath);
        File::delete($archive);

        $this->info("Installed database: {$databasePath}");
        $this->line('Size: '.number_format(File::size($databasePath) / 1048576, 2).' MB');

        return self::SUCCESS;
    }

    /**
     * Extract the archive and return the path to the .mmdb file.
     */
    protected function extract(string $archive, string $directory): ?string
    {
        if (! function_exists('exec')) {
            return null;
        }

        $target = $directory.'/extract-'.uniqid();
        File::ensureDirectoryExists($target);

        @exec('tar -xzf '.escapeshellarg($archive).' -C '.escapeshellarg($target).' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            return null;
        }

        $files = File::glob($target.'/*/*.mmdb');
        $mmdb = $files[0] ?? null;

        if ($mmdb === null) {
            return null;
        }

        $final = $directory.'/'.basename($mmdb);
        File::move($mmdb, $final);
        File::deleteDirectory($target);

        return $final;
    }
}
