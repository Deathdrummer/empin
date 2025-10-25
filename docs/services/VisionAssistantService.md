# VisionAssistantService - Документация

Сервис для работы с OpenAI Assistants API, предоставляющий возможности анализа изображений, чертежей и документов с помощью AI.

## Основные методы

### `use(array $config): self`

Переключение на другого ассистента с новой конфигурацией.

**Обязательные параметры:**
- `assistant_id` (string) - ID ассистента OpenAI

**Опциональные параметры:**
- `instruction_file` (string) - Путь к файлу с инструкциями
- `dir` (string) - Директория для файлов
- `model` (string) - Модель ('gpt-4', 'gpt-4-turbo-preview', 'o1-preview')
- `temperature` (float) - От 0 до 2
- `top_p` (float) - От 0 до 1
- `response_format` (string|array) - 'auto', 'text', 'json_object'
- `reasoning_effort` (string) - 'low', 'medium', 'high' (для o1/o3)

**Пример:**
```php
$service->use([
    'assistant_id' => 'asst_xxxxx',
    'model' => 'gpt-4-turbo-preview',
    'temperature' => 0.7
]);
```

---

### `ask(?string $prompt, UploadedFile|string|array|null $files, bool $saveThread = false): string`

Отправить запрос ассистенту и получить ответ.

**Параметры:**
- `$prompt` (string|null) - Текст вопроса/запроса
- `$files` (UploadedFile|string|array|null) - Файл(ы) для анализа:
  - `UploadedFile` - загруженный файл Laravel
  - `string` - путь к локальному файлу или URL
  - `array` - массив файлов (любой комбинации)
- `$saveThread` (bool) - Сохранить диалог (true) или удалить после ответа (false, по умолчанию)

**Возвращает:** Текстовый ответ ассистента

**Примеры:**
```php
// Простой вопрос
$answer = $service->ask('Что на чертеже?');

// С одним файлом
$answer = $service->ask('Проанализируй чертеж', $uploadedFile);

// С несколькими файлами
$answer = $service->ask('Сравни эти чертежи', [
    $file1,
    $file2,
    '/path/to/file3.pdf'
]);

// Сохранить диалог для продолжения
$answer = $service->ask('Первый вопрос', null, true);
$answer = $service->ask('Второй вопрос', null, true);
```

---

## Управление ассистентом

### Получение информации

```php
// Полная информация об ассистенте
$info = $service->getAssistant();

// Системные инструкции (system prompt)
$instructions = $service->getInstructions();

// Текущая модель
$model = $service->getModel();

// Формат ответа
$format = $service->getResponseFormat();

// Параметры генерации
$temp = $service->getTemperature();
$topP = $service->getTopP();

// Уровень reasoning effort (для o1/o3)
$effort = $service->getReasoningEffort();

// Список активных tools
$tools = $service->getTools();

// Список всех ассистентов
$assistants = $service->listAssistants(['limit' => 20]);

// Список всех моделей
$models = $service->listModels();
```

### Обновление параметров

```php
// Модель
$service->updateModel('gpt-4-turbo-preview');

// Системные инструкции
$service->updateInstructions('Ты эксперт по чертежам...');

// Формат ответа
$service->updateResponseFormat('json_object');

// Reasoning effort (для o1/o3)
$service->updateReasoningEffort('high'); // 'low', 'medium', 'high'

// Temperature
$service->updateTemperature(0.7); // 0-2

// Top P
$service->updateTopP(0.9); // 0-1

// Универсальное обновление
$service->updateAssistant([
    'model' => 'gpt-4-turbo-preview',
    'temperature' => 0.7,
    'response_format' => 'json_object'
]);
```

### Создание нового ассистента

```php
$newAssistant = $service->createAssistant([
    'model' => 'gpt-4-turbo-preview',
    'instructions' => 'Ты эксперт по анализу чертежей...',
    'name' => 'CAD Analyzer',
    'description' => 'Анализирует CAD чертежи',
    'tools' => [
        ['type' => 'file_search'],
        ['type' => 'code_interpreter']
    ],
    'temperature' => 0.7
]);
```

---

## Управление файлами

### Vector Store (для File Search)

Vector Store используется для хранения текстовых документов, которые ассистент может искать и анализировать.

**Поддерживаемые форматы:**
- Текстовые: txt, md, html, json, xml
- Код: c, cpp, cs, css, go, java, js, php, py, rb, sh, ts
- Документы: pdf, doc, docx, ppt, pptx

```php
// Добавить файл
$result = $service->addFileToVectorStore($uploadedFile);
// Вернёт: ['file_id' => '...', 'filename' => '...', 'size' => ..., 'size_mb' => ..., 'status' => 'added']

// Добавить файл по ID (если уже загружен в OpenAI)
$result = $service->addFileToVectorStoreById('file-xxx');

// Получить список всех файлов в Vector Store
$files = $service->getVectorStoreFiles();
// Вернёт массив: [['id' => '...', 'filename' => '...', 'size' => ..., 'size_mb' => ..., 'status' => '...'], ...]

// Удалить файл из Vector Store
$service->removeFileFromVectorStore('file-xxx');
```

### Code Interpreter

Code Interpreter используется для анализа данных, выполнения кода, работы с таблицами и изображениями.

**Поддерживаемые форматы:**
- Все форматы из Vector Store
- Дополнительно: csv, jpg, png, gif, tar, xlsx, zip

```php
// Добавить файл
$result = $service->addFileToCodeInterpreter($uploadedFile);
// Вернёт: ['file_id' => '...', 'filename' => '...', 'size' => ..., 'size_mb' => ..., 'status' => 'added']

// Добавить файл по ID
$result = $service->addFileToCodeInterpreterById('file-xxx');

// Получить список всех файлов в Code Interpreter
$files = $service->getCodeInterpreterFiles();

// Удалить файл из Code Interpreter
$service->removeFileFromCodeInterpreter('file-xxx');
```

### Общие операции с файлами

```php
// Получить информацию о файле
$info = $service->getFileInfo('file-xxx');

// Получить список всех файлов в OpenAI
$files = $service->listFiles();
$files = $service->listFiles('assistants'); // с фильтром по назначению

// Удалить файл из OpenAI полностью
$service->deleteFile('file-xxx');
```

---

## Управление Tools

```php
// Включить File Search
$service->enableFileSearch();

// Отключить File Search
$service->disableFileSearch();

// Включить Code Interpreter
$service->enableCodeInterpreter();

// Отключить Code Interpreter
$service->disableCodeInterpreter();

// Получить список активных tools
$tools = $service->getTools();
// Вернёт: [['type' => 'file_search'], ['type' => 'code_interpreter'], ...]
```

---

## Автоматическая синхронизация файлов

Сервис автоматически синхронизирует файлы из указанной директории (`dir` в конфиге, по умолчанию `assistent`):

- Файлы автоматически загружаются в OpenAI при изменении
- Текстовые документы добавляются в Vector Store
- Данные и изображения добавляются в Code Interpreter
- Удалённые файлы автоматически удаляются из OpenAI
- Файл инструкций (`instruction_file`) автоматически обновляется

**Пример структуры:**
```
storage/app/
  assistent/
    reference.pdf          # -> Vector Store
    data.csv              # -> Code Interpreter
    image.jpg             # -> Vision (вложение в thread)
  prompts/
    plan.txt              # -> System instructions
```

---

## Примеры использования

### Базовое использование

```php
use App\Services\VisionAssistantService;

$service = new VisionAssistantService([
    'assistant_id' => 'asst_xxxxx'
]);

$answer = $service->ask('Что на этом чертеже?', $uploadedFile);
```

### Работа с несколькими ассистентами

```php
$service = new VisionAssistantService();

// Ассистент для анализа чертежей
$service->use([
    'assistant_id' => 'asst_cad_analyzer',
    'model' => 'gpt-4-turbo-preview',
    'temperature' => 0.3
]);
$cadAnalysis = $service->ask('Проанализируй чертеж', $dwgFile);

// Ассистент для генерации описаний
$service->use([
    'assistant_id' => 'asst_description_writer',
    'model' => 'gpt-4',
    'temperature' => 0.8
]);
$description = $service->ask('Напиши описание на основе анализа: ' . $cadAnalysis);
```

### Диалоговый режим

```php
// Начать диалог
$answer1 = $service->ask('Что на чертеже?', $file, saveThread: true);

// Продолжить диалог (ассистент помнит контекст)
$answer2 = $service->ask('Какие размеры основной детали?', saveThread: true);
$answer3 = $service->ask('Есть ли ошибки?', saveThread: true);

// Завершить диалог (thread будет удалён)
$answer4 = $service->ask('Спасибо!', saveThread: false);
```

### Работа с JSON ответами

```php
$service->updateResponseFormat('json_object');

$answer = $service->ask('Верни структурированные данные о чертеже в JSON формате', $file);
$data = json_decode($answer, true);
```

### Использование reasoning моделей

```php
$service->use([
    'assistant_id' => 'asst_xxxxx',
    'model' => 'o1-preview',
    'reasoning_effort' => 'high'
]);

$answer = $service->ask('Проанализируй этот сложный чертеж и найди все потенциальные проблемы', $file);
```

---

## Кеширование

Сервис использует Laravel Cache для оптимизации:

- `openai.vision.thread_id` - ID активного диалога
- `openai.vision.file_map` - Карта загруженных файлов (хеши для отслеживания изменений)
- `openai.vision.vector_store_id` - ID Vector Store

Кеш автоматически очищается при:
- Переключении ассистента (`use()`)
- Изменении файлов в директории
- Изменении инструкций
- Обновлении параметров ассистента

---

## Конфигурация

### В .env

```env
OPENAI_API_KEY=sk-...
OPENAI_ASSISTANT_ID=asst_xxxxx
OPENAI_PROXY_URL=http://proxy:port  # опционально
```

### В config/openai.php

```php
return [
    'api_key' => env('OPENAI_API_KEY'),
    'assistant_id' => env('OPENAI_ASSISTANT_ID'),
    'proxy_url' => env('OPENAI_PROXY_URL'),
];
```

### При инициализации

```php
$service = new VisionAssistantService([
    'assistant_id' => 'asst_xxxxx',
    'instruction_file' => 'prompts/custom.txt',
    'dir' => 'my_files',
    'model' => 'gpt-4-turbo-preview',
    'temperature' => 0.7,
    'response_format' => 'json_object'
]);
```

---

## Ограничения и рекомендации

1. **Размер файлов**: максимум зависит от типа
   - Images: до 20MB
   - Documents: до 512MB для Vector Store

2. **Количество файлов**:
   - Code Interpreter: до 20 файлов на ассистента
   - Vector Store: до 10,000 файлов

3. **Threads**: При `saveThread=false` thread удаляется после ответа для экономии ресурсов

4. **Стоимость**: Учитывайте токены для:
   - Входящих сообщений
   - Vector Store ($0.10/GB/день)
   - Code Interpreter ($0.03 за сессию)

5. **Таймауты**: Запросы могут занимать до 1 часа (настроено в Guzzle)
