<?php

namespace Bkhim\Geolocation\Console;

use Bkhim\Geolocation\Models\LoginHistory;
use Bkhim\Geolocation\Geolocation;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditCommand extends Command
{
    protected $signature = 'geolocation:audit {--days=30 : Number of days to analyze}';
    protected $description = 'Generate a geolocation security audit report';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        $this->info("🔒 Geolocation Security Audit – " . date('F j, Y'));
        $this->line("Period: Last {$days} days");
        $this->line('');

        if (!DB::connection()->getSchemaBuilder()->hasTable('user_login_locations')) {
            $this->warn('No login history table found. Run migrations first.');
            return 0;
        }

        $logins = LoginHistory::where('occurred_at', '>=', now()->subDays($days));
        $totalLogins = $logins->count();

        if ($totalLogins === 0) {
            $this->info('No login data found for the specified period.');
            return 0;
        }

        $this->renderLoginLocationsSection($logins);
        $this->renderSecurityRecommendationsSection($logins);
        $this->renderComplianceSection($logins, $days);

        return 0;
    }

    protected function renderLoginLocationsSection($logins): void
    {
        $this->info('Login Locations:');
        
        $totalLogins = $logins->count();
        
        $newCountryLogins = (clone $logins)
            ->whereNotNull('country_code')
            ->select(DB::raw('COUNT(DISTINCT user_id) as cnt, country_code'))
            ->groupBy('country_code')
            ->having('cnt', '=', 1)
            ->count();
            
        $proxyLogins = (clone $logins)->where('is_proxy', true)->count();
        $torLogins = (clone $logins)->where('is_tor', true)->count();
        
        $uniqueCountries = (clone $logins)->distinct()->count('country_code');
        
        $this->line("├─ {$totalLogins} total logins");
        $this->line("├─ {$uniqueCountries} unique countries");
        $this->line("├─ {$newCountryLogins} logins from new countries");
        $this->line("├─ {$proxyLogins} VPN/proxy logins detected");
        $this->line("└─ {$torLogins} Tor exit node logins");
        $this->line('');
    }

    protected function renderSecurityRecommendationsSection($logins): void
    {
        $this->info('Recommendations:');
        
        $recommendations = $this->generateRecommendations($logins);
        
        if (empty($recommendations)) {
            $this->line('└─ No security issues detected');
        } else {
            $lastKey = array_key_last($recommendations);
            foreach ($recommendations as $key => $rec) {
                $prefix = $key === $lastKey ? '└─' : '├─';
                $this->line("{$prefix} {$rec}");
            }
        }
        $this->line('');
    }

    protected function generateRecommendations($logins): array
    {
        $recs = [];
        
        $proxyUsers = (clone $logins)
            ->where('is_proxy', true)
            ->distinct()
            ->count('user_id');
            
        if ($proxyUsers > 0) {
            $recs[] = "Enable MFA for users with VPN/proxy logins ({$proxyUsers} users)";
        }
        
        $highRiskCountries = (clone $logins)
            ->whereIn('country_code', ['CN', 'RU', 'KP', 'IR', 'SY'])
            ->distinct()
            ->count('user_id');
            
        if ($highRiskCountries > 0) {
            $recs[] = "Review users logging from high-risk countries ({$highRiskCountries} users)";
        }
        
        $nightLogins = (clone $logins)
            ->whereTime('occurred_at', '<', '05:00')
            ->count();
            
        if ($nightLogins > 0) {
            $recs[] = "Review {$nightLogins} logins occurring between midnight and 5AM";
        }
        
        $dataCenterLogins = (clone $logins)
            ->whereNotNull('city')
            ->where('city', 'like', '%Data%')
            ->count();
            
        if ($dataCenterLogins > 0) {
            $recs[] = "Block {$dataCenterLogins} logins from data center IPs";
        }
        
        return $recs;
    }

    protected function renderComplianceSection($logins, int $days): void
    {
        $this->info('Compliance:');
        
        $maskedIps = (clone $logins)->whereNotNull('ip_hash')->count();
        $totalLogins = (clone $logins)->count();
        $maskedPercent = $totalLogins > 0 ? round(($maskedIps / $totalLogins) * 100) : 0;
        
        $retentionDays = config('geolocation.user_trait.login_history_retention_days', 30);
        
        $this->line("├─ IP Masking: {$maskedPercent}% of IPs anonymized");
        $this->line("└─ Data Retention: {$retentionDays} days (configured)");
    }
}