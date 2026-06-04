<?php

namespace Bkhim\Geolocation\Contracts;

interface AuditLoggerInterface
{
    public function log(string $event, array $context = []): void;
}
