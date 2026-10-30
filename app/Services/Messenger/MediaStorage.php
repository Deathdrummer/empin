<?php

namespace App\Services\Messenger;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Вложения сообщений и комментариев: сохранение в storage/app/public и удаление
 */
class MediaStorage
{
    public const RULES = 'file|mimes:jpeg,jpg,png,gif,bmp,webp,heic,mp4,mov,avi,mkv,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,txt,csv,mp3,wav,ogg,aac,flac,m4a|max:307200';

    // Расширения, которые всегда звук, даже если содержимое похоже на видео-контейнер
    private const AUDIO_EXTENSIONS = [
        'm4a'  => 'audio/mp4',
        'aac'  => 'audio/aac',
        'mp3'  => 'audio/mpeg',
        'ogg'  => 'audio/ogg',
        'opus' => 'audio/ogg',
        'wav'  => 'audio/wav',
        'flac' => 'audio/flac',
    ];

    private const DOCUMENT_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
    ];

    private const ARCHIVE_TYPES = [
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ];

    // Сведения о вложении от приложения (необязательны; старые версии их не шлют)
    public const META_RULES = [
        'media_meta'    => 'nullable|array|max:20',
        'media_meta.*'  => 'nullable|string|max:4000',
        'media_thumb'   => 'nullable|array|max:20',
        'media_thumb.*' => 'file|mimes:jpeg,jpg,png,webp|max:1024',
    ];

    private const WAVEFORM_BARS = 100;
    private const WAVEFORM_MAX = 31;

    /**
     * Вложения из запроса. К каждому файлу приложение может приложить (тем же порядковым номером):
     * media_meta[i] — JSON {waveform: [0..31], duration: сек, width, height} — «волна» голосового, длительность,
     * размеры видео; media_thumb[i] — кадр-превью видео (снят на телефоне, как в Telegram).
     *
     * @return array<int, array{path: string, type: string, mime_type: string, size: int, name: string}>
     */
    public static function store(Request $request, string $directory): array
    {
        if (!$request->hasFile('media')) {
            return [];
        }

        $metaList = (array) $request->input('media_meta', []);
        $thumbs = (array) $request->file('media_thumb', []);

        $media = [];
        foreach (array_values((array) $request->file('media')) as $index => $file) {
            $item = self::storeFile($file, $directory);
            $item += self::sanitizeMeta($metaList[$index] ?? null);
            if ($item['type'] === 'video' && isset($thumbs[$index])) {
                $item['thumbnail'] = '/storage/' . $thumbs[$index]->store($directory . '/thumbs', 'public');
            }
            $media[] = $item;
        }

        return $media;
    }

    /**
     * Только числа в допустимых пределах: «волна» — до 100 столбиков 0…31, длительность — до 10 часов
     */
    private static function sanitizeMeta($raw): array
    {
        $meta = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($meta)) {
            return [];
        }

        $result = [];
        if (is_array($meta['waveform'] ?? null)) {
            $bars = array_slice(array_values(array_filter($meta['waveform'], 'is_numeric')), 0, self::WAVEFORM_BARS);
            if ($bars) {
                $result['waveform'] = array_map(fn ($bar) => max(0, min(self::WAVEFORM_MAX, (int) round($bar))), $bars);
            }
        }
        if (is_numeric($meta['duration'] ?? null) && $meta['duration'] > 0 && $meta['duration'] <= 36000) {
            $result['duration'] = round((float) $meta['duration'], 1);
        }
        foreach (['width', 'height'] as $key) {
            if (is_numeric($meta[$key] ?? null) && $meta[$key] >= 1 && $meta[$key] <= 20000) {
                $result[$key] = (int) $meta[$key];
            }
        }

        return $result;
    }

    private static function storeFile($file, string $directory): array
    {
        $mimeType = (string) $file->getMimeType();
        $extension = strtolower((string) $file->getClientOriginalExtension());

        // Голосовое m4a/aac — MP4-контейнер только со звуком: по содержимому определяется как видео
        // (так пишет диктофон Android). Тип берём по расширению, файл сохраняем с ним же.
        $audioMime = self::AUDIO_EXTENSIONS[$extension] ?? null;
        if ($audioMime && !str_starts_with($mimeType, 'audio/')) {
            $mimeType = $audioMime;
            $stored = $file->storeAs($directory, Str::random(40) . '.' . $extension, 'public');
        } else {
            $stored = $file->store($directory, 'public');
        }

        return [
            'path'      => '/storage/' . $stored,
            'type'      => self::typeOf($mimeType),
            'mime_type' => $mimeType,
            'size'      => $file->getSize(),
            // Android присылает имя в URL-кодировке («%D0%93…»)
            'name'      => rawurldecode($file->getClientOriginalName()),
        ];
    }

    /**
     * Тип вложения, сохранённого раньше: голосовые с Android попадали в «видео» (см. store) —
     * по имени файла они аудио. Для показа старых сообщений.
     */
    public static function fixLegacyType(array $item): array
    {
        $extension = strtolower(pathinfo(rawurldecode((string) ($item['name'] ?? '')), PATHINFO_EXTENSION));
        $audioMime = self::AUDIO_EXTENSIONS[$extension] ?? null;
        if ($audioMime && ($item['type'] ?? null) === 'video') {
            $item['type'] = 'audio';
            $item['mime_type'] = $audioMime;
        }

        return $item;
    }

    /**
     * Копия вложений для пересланного сообщения: у копии свои файлы — если автор удалит оригинал,
     * у получателя вложение не пропадёт. Отсутствующие файлы пропускаются.
     *
     * @return array<int, array{path: string, type: string, mime_type: string, size: int, name: string}>
     */
    public static function copy($media, string $directory): array
    {
        if (!$media || !is_array($media)) {
            return [];
        }

        $disk = Storage::disk('public');
        $copies = [];
        // Старый формат — одно вложение объектом
        foreach (isset($media['path']) ? [$media] : $media as $item) {
            $source = str_replace('/storage/', '', (string) ($item['path'] ?? ''));
            if ($source === '' || !$disk->exists($source)) {
                continue;
            }

            $copy = array_merge($item, ['path' => '/storage/' . self::copyFile($source, trim($directory, '/'))]);
            // Кадр-превью видео — тоже своя копия; пропал — копия без превью
            unset($copy['thumbnail']);
            $thumbSource = str_replace('/storage/', '', (string) ($item['thumbnail'] ?? ''));
            if ($thumbSource !== '' && $disk->exists($thumbSource)) {
                $copy['thumbnail'] = '/storage/' . self::copyFile($thumbSource, trim($directory, '/') . '/thumbs');
            }
            $copies[] = $copy;
        }

        return $copies;
    }

    private static function copyFile(string $source, string $directory): string
    {
        $extension = pathinfo($source, PATHINFO_EXTENSION);
        $target = $directory . '/' . Str::random(40) . ($extension !== '' ? ".{$extension}" : '');
        Storage::disk('public')->copy($source, $target);

        return $target;
    }

    public static function delete($media): void
    {
        if (!$media || !is_array($media)) {
            return;
        }

        // Старый формат — одно вложение объектом; кадр-превью видео удаляется вместе с ним
        foreach (isset($media['path']) ? [$media] : $media as $item) {
            foreach (['path', 'thumbnail'] as $key) {
                if (!empty($item[$key])) {
                    $path = str_replace('/storage/', '', $item[$key]);
                    if (Storage::disk('public')->exists($path)) {
                        Storage::disk('public')->delete($path);
                    }
                }
            }
        }
    }

    private static function typeOf(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            in_array($mimeType, self::DOCUMENT_TYPES, true) => 'document',
            in_array($mimeType, self::ARCHIVE_TYPES, true) => 'archive',
            default => 'file',
        };
    }
}
