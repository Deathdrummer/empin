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

    /**
     * @return array<int, array{path: string, type: string, mime_type: string, size: int, name: string}>
     */
    public static function store(Request $request, string $directory): array
    {
        if (!$request->hasFile('media')) {
            return [];
        }

        $media = [];
        foreach ((array) $request->file('media') as $file) {
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

            $media[] = [
                'path'      => '/storage/' . $stored,
                'type'      => self::typeOf($mimeType),
                'mime_type' => $mimeType,
                'size'      => $file->getSize(),
                // Android присылает имя в URL-кодировке («%D0%93…»)
                'name'      => rawurldecode($file->getClientOriginalName()),
            ];
        }

        return $media;
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

            $extension = pathinfo($source, PATHINFO_EXTENSION);
            $target = trim($directory, '/') . '/' . Str::random(40) . ($extension !== '' ? ".{$extension}" : '');
            $disk->copy($source, $target);

            $copies[] = array_merge($item, ['path' => '/storage/' . $target]);
        }

        return $copies;
    }

    public static function delete($media): void
    {
        if (!$media || !is_array($media)) {
            return;
        }

        // Старый формат — одно вложение объектом
        foreach (isset($media['path']) ? [$media] : $media as $item) {
            if (!empty($item['path'])) {
                $path = str_replace('/storage/', '', $item['path']);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
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
