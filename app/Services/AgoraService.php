<?php

namespace App\Services;

/**
 * Agora RTC Service
 *
 * Для генерации токена использует официальный Agora PHP Token Builder.
 *
 * УСТАНОВКА (один раз):
 *   composer require agora/agora-token
 *
 * Если пакет недоступен — скачай вручную с GitHub и положи в app/Services/Agora/:
 *   https://github.com/AgoraIO/Tools/tree/master/DynamicKey/AgoraDynamicKey/php/src
 *   Нужны файлы: AccessToken2.php, RtcTokenBuilder2.php
 *   Затем раскомментируй require ниже.
 */
class AgoraService
{
    public const ROLE_PUBLISHER  = 1;
    public const ROLE_SUBSCRIBER = 2;

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

    /**
     * Генерирует уникальное имя канала.
     */
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
        // Используем официальный Agora PHP Token Builder через Composer:
        //   composer require agora/agora-token
        // Документация: https://docs.agora.io/en/video-calling/get-started/authentication-workflow

        $tokenExpire     = $expireSeconds;
        $privilegeExpire = $expireSeconds;

        // После composer require agora/agora-token:
        $token = \Agora\Token\RtcTokenBuilder::buildTokenWithUid(
            $this->appId,
            $this->appCertificate,
            $channelName,
            $uid,
            self::ROLE_PUBLISHER,
            $tokenExpire,
            $privilegeExpire
        );

        return $token;
    }
}
