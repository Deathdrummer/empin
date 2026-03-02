<?php

namespace App\Services;

require_once __DIR__ . '/Agora/RtcTokenBuilder2.php';

/**
 * Agora RTC Service
 * Использует официальный Agora PHP Token Builder (app/Services/Agora/).
 */
class AgoraService
{
    private string $appId;
    private string $appCertificate;

    public function __construct()
    {
        $this->appId          = config('services.agora.app_id');
        $this->appCertificate = config('services.agora.app_certificate');
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public static function generateChannelName(): string
    {
        return 'call-' . \Str::uuid()->toString();
    }

    /**
     * Генерирует Agora RTC токен для участника.
     *
     * @param string $channelName  Имя канала
     * @param int    $uid          Числовой ID (staff_id)
     * @param int    $expireSeconds Срок жизни токена
     */
    public function generateToken(
        string $channelName,
        int    $uid,
        int    $expireSeconds = 3600
    ): string {
        return \RtcTokenBuilder2::buildTokenWithUid(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $uid,
            \RtcTokenBuilder2::ROLE_PUBLISHER,
            $expireSeconds,
            $expireSeconds
        );
    }
}
