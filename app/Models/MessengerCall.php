<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'caller_id',
        'callee_id',
        'call_type',
        'status',
        'started_at',
        'ended_at',
        'duration',
        'quality_rating',
        'session_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration' => 'integer',
        'quality_rating' => 'integer',
    ];

    /**
     * Звонящий пользователь
     */
    public function caller(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'caller_id');
    }

    /**
     * Принимающий звонок пользователь
     */
    public function callee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'callee_id');
    }

    /**
     * Scope: звонки где пользователь участвует
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('caller_id', $userId)
                ->orWhere('callee_id', $userId);
        });
    }

    /**
     * Scope: входящие звонки для пользователя
     */
    public function scopeIncoming($query, int $userId)
    {
        return $query->where('callee_id', $userId);
    }

    /**
     * Scope: исходящие звонки пользователя
     */
    public function scopeOutgoing($query, int $userId)
    {
        return $query->where('caller_id', $userId);
    }

    /**
     * Scope: пропущенные звонки
     */
    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    /**
     * Определить направление звонка для конкретного пользователя
     */
    public function getDirectionForUser(int $userId): string
    {
        if ($this->caller_id === $userId) {
            return 'outgoing';
        }

        if ($this->callee_id === $userId) {
            return 'incoming';
        }

        return 'unknown';
    }

    /**
     * Вычислить и сохранить длительность звонка
     */
    public function calculateDuration(): void
    {
        if ($this->started_at && $this->ended_at) {
            $this->duration = $this->ended_at->diffInSeconds($this->started_at);
            $this->save();
        }
    }
}
