<?php namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessengerMessageResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request) {
        $currentUser = $request->user();

        // Определяем является ли текущий пользователь автором сообщения
        $isSelf = $currentUser && $this->from_id ? ($this->from_id === $currentUser->staff_id) : false;

        // Обрабатываем media - добавляем отсутствующие поля
        $mediaWithDefaults = null;
        if ($this->media) {
            $mediaWithDefaults = array_map(function($m) {
                // Если name отсутствует - извлекаем из filename или path
                if (empty($m['name'])) {
                    // Проверяем старое поле filename
                    if (!empty($m['filename'])) {
                        $m['name'] = $m['filename'];
                    }
                    // Извлекаем из path
                    elseif (!empty($m['path'])) {
                        $pathWithoutQuery = explode('?', $m['path'])[0];
                        $fileName = basename($pathWithoutQuery);
                        $m['name'] = $fileName ?: null;
                    }
                }

                // Декодируем URL-encoded имя
                if (!empty($m['name']) && strpos($m['name'], '%') !== false) {
                    $m['name'] = urldecode($m['name']);
                }

                // Если size отсутствует - пытаемся получить из файла
                if (empty($m['size']) && !empty($m['path'])) {
                    $storagePath = str_replace('/storage/', '', $m['path']);
                    $fullPath = storage_path('app/public/' . $storagePath);
                    if (file_exists($fullPath)) {
                        $m['size'] = filesize($fullPath);
                    } else {
                        \Log::warning('[MessengerMessageResource] File not found', [
                            'message_id' => $this->id,
                            'path' => $fullPath
                        ]);
                    }
                }

                return $m;
            }, $this->media);
        }

        return [
            'id'          => $this->id,
            'chat_id'     => $this->chat_id,
            'from'        => $this->profile ? [
                                'sname' => $this->profile->sname,
                                'fname' => $this->profile->fname,
                                'mname' => $this->profile->mname,
                            ] : null,
            'message'     => $this->message,
            'reply_to_id' => $this->reply_to_id,
            'reactions'   => $this->reactions ?? [],
            'media'       => $mediaWithDefaults,
            'self'        => $isSelf,
            'created_at'  => $this->created_at->toIso8601String(),
            'updated_at'  => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
