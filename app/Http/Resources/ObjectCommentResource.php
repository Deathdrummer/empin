<?php namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Комментарий табеля в чате объекта — в том же виде, что сообщение мессенджера.
 * context — день табеля и бригада, к которым относится комментарий.
 */
class ObjectCommentResource extends JsonResource {

    public static $wrap = null;

    private ?int $chatId = null;

    public static function forChat($comment, int $chatId): self {
        $resource = new self($comment);
        $resource->chatId = $chatId;

        return $resource;
    }

    public function toArray($request) {
        $me = $request->user()?->staff_id;
        $team = $this->relationLoaded('timesheetContract') ? $this->timesheetContract?->team : null;
        $master = $team && $team->relationLoaded('profile') ? $team->profile : null;
        $day = $team?->getRawOriginal('day');

        return [
            'id'          => $this->id,
            'chat_id'     => $this->chatId,
            'from_id'     => $this->from_id,
            'from'        => $this->profile ? [
                                'sname' => $this->profile->sname,
                                'fname' => $this->profile->fname,
                                'mname' => $this->profile->mname,
                            ] : null,
            'message'     => $this->message,
            'reply_to_id' => $this->reply_to_id,
            'reply_to'    => $this->when($this->relationLoaded('replyTo') && $this->replyTo, fn () => [
                'id'      => $this->replyTo->id,
                'from_id' => $this->replyTo->from_id,
                'from'    => $this->replyTo->relationLoaded('profile') && $this->replyTo->profile ? [
                    'sname' => $this->replyTo->profile->sname,
                    'fname' => $this->replyTo->profile->fname,
                ] : null,
                'message' => mb_substr((string) $this->replyTo->message, 0, 200),
                'type'    => MessengerMessageResource::previewType($this->replyTo->media),
            ]),
            'reactions'   => $this->reactions ?? [],
            'media'       => MessengerMessageResource::normalizeMedia($this->media, $this->id),
            'type'        => 'text',
            'mentions'    => [],
            'pinned'      => $this->pinned_at !== null,
            'self'        => $me !== null && (int) $this->from_id === (int) $me,
            'context'     => [
                'day'  => $day ? substr((string) $day, 0, 10) : null,
                'team' => $master ? trim($master->sname . ' ' . mb_substr((string) $master->fname, 0, 1) . '.') : null,
            ],
            'created_at'  => $this->created_at->toIso8601String(),
            'updated_at'  => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
