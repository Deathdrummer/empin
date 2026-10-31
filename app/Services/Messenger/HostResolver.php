<?php

namespace App\Services\Messenger;

/**
 * Адреса хоста для превью ссылок (отдельно — чтобы в тестах подменять DNS)
 */
class HostResolver
{
    /** @return string[] IPv4 и IPv6 */
    public function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];
        $records = @dns_get_record($host, DNS_AAAA) ?: [];
        foreach ($records as $record) {
            if (!empty($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return array_values(array_unique($ips));
    }
}
