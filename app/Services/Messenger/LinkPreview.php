<?php

namespace App\Services\Messenger;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Превью ссылки, как в Telegram: название сайта, заголовок, описание и картинка (Open Graph / meta / title).
 *
 * Сервер ходит по чужим адресам — поэтому защита от обращений во внутреннюю сеть (SSRF):
 * только http/https на стандартных портах; все адреса хоста — публичные (не локальные, не частные, не служебные);
 * соединение идёт на уже проверенный адрес (повторный DNS-запрос не подменит его — DNS rebinding);
 * переадресации — вручную, каждая проверяется заново; ограничены время и размер ответа.
 * Картинка скачивается сервером и отдаётся со своего домена: телефон не ходит на чужие сайты.
 * Результат (и его отсутствие) кэшируется — один адрес не запрашивается снова и снова.
 */
class LinkPreview
{
    private const MAX_REDIRECTS = 3;
    private const TIMEOUT_SECONDS = 5;
    private const MAX_HTML_BYTES = 1024 * 1024;
    private const MAX_IMAGE_BYTES = 2 * 1024 * 1024;
    private const CACHE_HOURS = 24;
    private const NOTHING_CACHE_HOURS = 1;
    private const USER_AGENT = 'Mozilla/5.0 (compatible; EmpinPreview/1.0; +https://empin-pro.ru)';
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    // Не проверяются фильтром PHP: общий адрес провайдеров (CGNAT) и тестовые сети
    private const EXTRA_BLOCKED_V4 = ['100.64.0.0/10', '192.0.0.0/24', '198.18.0.0/15'];

    public function __construct(private HostResolver $resolver)
    {
    }

    /** @return array{url: string, site: string, title: ?string, description: ?string, image: ?string, image_width: ?int, image_height: ?int}|null */
    public function for(string $url): ?array
    {
        $key = 'messenger:link-preview:' . sha1($url);
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached['preview'] ?? null;
        }

        try {
            $preview = $this->build($url);
        } catch (\Throwable $error) {
            Log::info('[Messenger] Link preview failed', ['url' => $url, 'error' => $error->getMessage()]);
            $preview = null;
        }
        Cache::put($key, ['preview' => $preview], now()->addHours($preview ? self::CACHE_HOURS : self::NOTHING_CACHE_HOURS));

        return $preview;
    }

    private function build(string $url): ?array
    {
        [$response, $finalUrl] = $this->get($url, ['text/html', 'application/xhtml+xml'], self::MAX_HTML_BYTES);
        if (!$response) {
            return null;
        }

        $meta = $this->parse($this->utf8($response), $finalUrl);
        if (!$meta['title'] && !$meta['description']) {
            return null;
        }

        $image = $meta['image'] ? $this->storeImage($meta['image']) : null;

        return [
            'url'          => $finalUrl,
            'site'         => $meta['site'] ?: (string) parse_url($finalUrl, PHP_URL_HOST),
            'title'        => $meta['title'],
            'description'  => $meta['description'],
            'image'        => $image['path'] ?? null,
            'image_width'  => $image['width'] ?? null,
            'image_height' => $image['height'] ?? null,
        ];
    }

    /**
     * GET с проверкой каждого адреса и переадресации
     *
     * @param string[] $types допустимые Content-Type
     * @return array{0: ?Response, 1: string}
     */
    private function get(string $url, array $types, int $maxBytes): array
    {
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            $target = $this->safeTarget($url);
            if (!$target) {
                return [null, $url];
            }

            $response = Http::withHeaders(['User-Agent' => self::USER_AGENT, 'Accept-Language' => 'ru,en;q=0.8'])
                ->withoutRedirecting()
                ->timeout(self::TIMEOUT_SECONDS)
                ->connectTimeout(3)
                ->withOptions([
                    // Соединение — только на проверенный адрес
                    'curl' => [CURLOPT_RESOLVE => ["{$target['host']}:{$target['port']}:{$target['ip']}"]],
                    // Большой ответ обрываем, не дочитывая
                    'progress' => function ($total, $downloaded) use ($maxBytes) {
                        if ($total > $maxBytes || $downloaded > $maxBytes) {
                            throw new \RuntimeException('Response too large');
                        }
                    },
                ])
                ->get($url);

            if ($response->redirect()) {
                $location = $response->header('Location');
                if (!$location) {
                    return [null, $url];
                }
                $url = $this->absolute($location, $url);
                continue;
            }

            if (!$response->successful() || strlen($response->body()) > $maxBytes) {
                return [null, $url];
            }
            $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            if (!in_array($type, $types, true)) {
                return [null, $url];
            }

            return [$response, $url];
        }

        return [null, $url];
    }

    /**
     * Можно ли идти по адресу: схема, порт и все адреса хоста — публичные
     *
     * @return array{host: string, port: int, ip: string}|null
     */
    private function safeTarget(string $url): ?array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (!in_array($port, [80, 443], true) || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return null;
        }

        $ips = $this->resolver->resolve($host);
        if (!$ips) {
            return null;
        }
        foreach ($ips as $ip) {
            if (!$this->isPublic($ip)) {
                return null;
            }
        }

        return ['host' => $host, 'port' => $port, 'ip' => str_contains($ips[0], ':') ? "[{$ips[0]}]" : $ips[0]];
    }

    private function isPublic(string $ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            foreach (self::EXTRA_BLOCKED_V4 as $range) {
                [$subnet, $bits] = explode('/', $range);
                $mask = -1 << (32 - (int) $bits);
                if ((ip2long($ip) & $mask) === (ip2long($subnet) & $mask)) {
                    return false;
                }
            }

            return true;
        }

        // IPv6 с IPv4 внутри (::ffff:10.0.0.1) — по вложенному адресу
        $packed = inet_pton($ip);
        if ($packed !== false && str_starts_with($packed, str_repeat("\0", 10) . "\xff\xff")) {
            return $this->isPublic(inet_ntop(substr($packed, 12)));
        }

        return true;
    }

    /** Текст страницы в UTF-8: русские сайты бывают в windows-1251 (charset в заголовке или meta) */
    private function utf8(Response $response): string
    {
        $html = $response->body();
        $charset = null;
        if (preg_match('/charset=([\w-]+)/i', (string) $response->header('Content-Type'), $match)) {
            $charset = $match[1];
        } elseif (preg_match('/<meta[^>]+charset=["\']?([\w-]+)/i', substr($html, 0, 4096), $match)) {
            $charset = $match[1];
        }
        $charset = strtoupper((string) $charset);
        if ($charset && !in_array($charset, ['UTF-8', 'UTF8'], true) && in_array($charset, array_map('strtoupper', mb_list_encodings()), true)) {
            $html = mb_convert_encoding($html, 'UTF-8', $charset);
        }

        // Текст уже в UTF-8 — а разборщик HTML послушался бы <meta charset="windows-1251"> и испортил его
        return preg_replace('/(<meta[^>]+charset=["\']?)[\w-]+/i', '${1}utf-8', $html);
    }

    /** @return array{site: ?string, title: ?string, description: ?string, image: ?string} */
    private function parse(string $html, string $baseUrl): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);

        $meta = function (array $names) use ($xpath): ?string {
            foreach ($names as $name) {
                foreach (['property', 'name'] as $attribute) {
                    $node = $xpath->query("//meta[@{$attribute}='{$name}']/@content")->item(0);
                    $value = $node ? $this->clean($node->nodeValue) : null;
                    if ($value) {
                        return $value;
                    }
                }
            }

            return null;
        };

        $titleNode = $xpath->query('//title')->item(0);
        $image = $meta(['og:image:secure_url', 'og:image', 'twitter:image', 'twitter:image:src']);

        return [
            'site'        => $this->limit($meta(['og:site_name', 'application-name']), 80),
            'title'       => $this->limit($meta(['og:title', 'twitter:title']) ?? ($titleNode ? $this->clean($titleNode->textContent) : null), 200),
            'description' => $this->limit($meta(['og:description', 'twitter:description', 'description']), 300),
            'image'       => $image ? $this->absolute($image, $baseUrl) : null,
        ];
    }

    /** @return array{path: string, width: int, height: int}|null */
    private function storeImage(string $url): ?array
    {
        try {
            [$response] = $this->get($url, array_keys(self::IMAGE_TYPES), self::MAX_IMAGE_BYTES);
        } catch (\Throwable $error) {
            return null;
        }
        if (!$response) {
            return null;
        }

        $body = $response->body();
        // Проверяем, что это действительно картинка (не по заголовку), и берём размеры — для вёрстки до загрузки
        $size = @getimagesizefromstring($body);
        if (!$size || empty(self::IMAGE_TYPES[$size['mime'] ?? '']) || $size[0] < 32 || $size[1] < 32) {
            return null;
        }

        $path = 'messenger/previews/' . sha1($url) . '.' . self::IMAGE_TYPES[$size['mime']];
        Storage::disk('public')->put($path, $body);

        return ['path' => '/storage/' . $path, 'width' => (int) $size[0], 'height' => (int) $size[1]];
    }

    private function absolute(string $location, string $base): string
    {
        $location = trim(html_entity_decode($location, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (preg_match('~^https?://~i', $location)) {
            return $location;
        }
        $parts = parse_url($base);
        $origin = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
        if (str_starts_with($location, '//')) {
            return ($parts['scheme'] ?? 'https') . ':' . $location;
        }
        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }
        $dir = preg_replace('~/[^/]*$~', '/', $parts['path'] ?? '/');

        return $origin . ($dir ?: '/') . $location;
    }

    private function clean(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $value !== '' ? $value : null;
    }

    private function limit(?string $value, int $length): ?string
    {
        return $value === null ? null : Str::limit($value, $length, '…');
    }
}
