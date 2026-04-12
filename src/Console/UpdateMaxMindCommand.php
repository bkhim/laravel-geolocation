<?php

namespace Bkhim\Geolocation\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class UpdateMaxMindCommand extends Command
{
    protected $signature = 'geolocation:update-maxmind {--dry-run : Show what would be downloaded without actually downloading}';
    protected $description = 'Download/update MaxMind database for IP geolocation';

    public function handle(): int
    {
        $licenseKey = config('geolocation.providers.maxmind.license_key');
        
        if (!$licenseKey) {
            $this->error('MaxMind license key not configured. Set GEOLOCATION_MAXMIND_LICENSE_KEY in .env');
            return 1;
        }

        $databasePath = config('geolocation.providers.maxmind.database_path');
        
        if (!$databasePath) {
            $this->error('MaxMind database path not configured.');
            return 1;
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run mode - would download from: https://download.maxmind.com/app/geoip_download');
            $this->info("Target: {$databasePath}");
            return 0;
        }

        $this->info('Downloading MaxMind database...');
        
        $url = 'https://download.maxmind.com/app/geoip_download';
        $params = [
            'edition_id' => 'GeoLite2-City',
            'license_key' => $licenseKey,
            'suffix' => 'tar.gz',
        ];

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel-Geolocation/1.0',
            ])->timeout(300)->get($url, $params);
            
            if ($response->failed()) {
                $this->error('Download failed: ' . $response->status());
                return 1;
            }

            $tmpPath = storage_path('app/geoip/GeoLite2-City.tar.gz');
            File::put($tmpPath, $response->body());
            
            $this->info("Downloaded to: {$tmpPath}");
            $this->info('Extract and replace the .mmdb file manually');
            $this->line('Run: tar -xzf ' . $tmpPath);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Download failed: ' . $e->getMessage());
            return 1;
        }
    }
}