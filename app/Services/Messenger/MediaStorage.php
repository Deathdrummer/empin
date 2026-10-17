<?php

namespace App\Services\Messenger;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
