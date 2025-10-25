<?php
/**
 * Тест скорости прокси для OpenAI API
 * Запуск: php test_proxy_speed.php
 */

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

// Загружаем конфигурацию
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$proxyUrl = $_ENV['OPENAI_PROXY_URL'] ?? null;
$apiKey = $_ENV['OPENAI_API_KEY'] ?? null;

if (!$apiKey) {
    die("❌ OPENAI_API_KEY не найден в .env\n");
}

echo "🔍 Тестирование скорости подключения к OpenAI API\n";
echo str_repeat("=", 60) . "\n";

// Тест 1: Прямое подключение (без прокси)
echo "\n📡 Тест 1: Прямое подключение (без прокси)\n";
testConnection($apiKey, null);

// Тест 2: Через прокси (если настроен)
if ($proxyUrl) {
    echo "\n🔐 Тест 2: Через прокси ($proxyUrl)\n";
    testConnection($apiKey, $proxyUrl);
} else {
    echo "\n⚠️  Прокси не настроен в .env (OPENAI_PROXY_URL)\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ Тестирование завершено\n";

/**
 * Тестирует подключение к OpenAI API
 */
function testConnection(string $apiKey, ?string $proxy): void
{
    $client = new Client([
        'base_uri' => 'https://api.openai.com',
        'timeout' => 30,
        'verify' => false,
        'proxy' => $proxy ? [
            'http' => $proxy,
            'https' => $proxy,
        ] : null,
    ]);

    try {
        // Замеряем время запроса
        $startTime = microtime(true);

        $response = $client->get('/v1/models', [
            'headers' => [
                'Authorization' => "Bearer $apiKey",
            ],
        ]);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody()->getContents(), true);
        $modelsCount = count($body['data'] ?? []);

        echo "   ✅ Успешно\n";
        echo "   ⏱️  Время ответа: {$duration} мс\n";
        echo "   📊 HTTP статус: {$statusCode}\n";
        echo "   📦 Моделей получено: {$modelsCount}\n";

        // Оценка скорости
        if ($duration < 500) {
            echo "   🚀 Отличная скорость!\n";
        } elseif ($duration < 1500) {
            echo "   ✅ Хорошая скорость\n";
        } elseif ($duration < 3000) {
            echo "   ⚠️  Средняя скорость (может тормозить)\n";
        } else {
            echo "   ❌ Медленная скорость (рекомендуется сменить прокси)\n";
        }

    } catch (\Exception $e) {
        echo "   ❌ Ошибка: " . $e->getMessage() . "\n";

        if ($e instanceof \GuzzleHttp\Exception\ConnectException) {
            echo "   💡 Проверьте:\n";
            echo "      - Доступность прокси сервера\n";
            echo "      - Правильность URL прокси в .env\n";
            echo "      - Интернет соединение\n";
        }
    }
}
