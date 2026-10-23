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

            $media[] = [
                'path'      => '/storage/' . $file->store($directory, 'public'),
                'type'      => self::typeOf($mimeType),
                'mime_type' => $mimeType,
                'size'      => $file->getSize(),
                'name'      => $file->getClientOriginalName(),
            ];
        }

        return $media;
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
