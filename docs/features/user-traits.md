# User Traits

Detect user connection type, device type, and network characteristics.

## Connection Properties

```php
$details = Geolocation::lookup('8.8.8.8');

// Network type
echo $details->getConnectionType(); 
// Options: residential, corporate, datacenter, dialup, satellite, broadband

// ISP/Organization
echo $details->getIsp();           // Google LLC
echo $details->getOrg();            // Google LLC

// ASN (Autonomous System Number)
echo $details->getAsn();            // AS15169
echo $details->getAsnName();        // Google LLC

// Hostname
echo $details->getHostname();       // dns.google
```

## Device Detection

```php
$details = Geolocation::lookup($request->ip());

// Is this a mobile device?
if ($details->isMobile()) {
    // Optimize for mobile
}

// What type of connection?
switch ($details->getConnectionType()) {
    case 'datacenter':
        // Likely a bot or automated script
        break;
    case 'residential':
        // Regular home user
        break;
    case 'corporate':
        // Business connection
        break;
    case 'mobile':
        // Mobile carrier
        break;
}
```

## ISP Reputation

```php
$details = Geolocation::lookup($request->ip());

// Check if known datacenter/provider
$datacenterISPs = ['Amazon.com', 'Google Cloud', 'Microsoft Azure', 'DigitalOcean'];

if (in_array($details->getIsp(), $datacenterISPs)) {
    // Datacenter IP - could be legitimate or bot
    Log::info('Datacenter IP detected: ' . $details->getIp());
}
```

## Trait Summary

| Method | Description | Example |
|--------|-------------|---------|
| `isMobile()` | Mobile connection | `true`/`false` |
| `isProxy()` | Proxy/VPN detected | `true`/`false` |
| `isCrawler()` | Bot/crawler | `true`/`false` |
| `isTor()` | Tor exit node | `true`/`false` |
| `getIsp()` | ISP name | "Google LLC" |
| `getOrg()` | Organization | "Google LLC" |
| `getAsn()` | ASN | "AS15169" |
| `getAsnName()` | ASN name | "Google LLC" |
| `getConnectionType()` | Connection type | "residential" |
| `getHostname()` | Reverse DNS | "dns.google" |
