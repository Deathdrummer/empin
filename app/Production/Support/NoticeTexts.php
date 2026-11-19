<?php

namespace App\Production\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Части текстов уведомлений — обычными предложениями (решение 19.11.2026): «за 18 ноября по объекту 12 «Название»»,
 * «бригады Петров», род глагола по отчеству
 */
final class NoticeTexts
{
    private const MONTHS = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];

    /** «за 18 ноября по объекту 12 «Название»»; год — только если не текущий */
    public static function about(?string $number, ?string $title, CarbonInterface|string $day, CarbonInterface $today): string
    {
        return sprintf('за %s по объекту %s', self::date($day, $today), self::object($number, $title));
    }

    /** «12 «Название»» */
    public static function object(?string $number, ?string $title): string
    {
        return trim(($number ?? '').' «'.($title ?? '').'»');
    }

    /** «18 ноября», «18 ноября 2025» */
    public static function date(CarbonInterface|string $day, CarbonInterface $today): string
    {
        $date = $day instanceof CarbonInterface ? $day : CarbonImmutable::parse($day);
        $text = $date->day.' '.self::MONTHS[$date->month - 1];

        return $date->year === $today->year ? $text : $text.' '.$date->year;
    }

    /**
     * Название бригады после слова «бригада» в предложении: «Бригада Петров» → «Петров» (чтобы не вышло
     * «бригады «Бригада Петров»»), остальные — в кавычках: «бригады «Альфа»». Название в ERP — свободный текст [инт. 7.14]
     */
    public static function brigade(?string $name): string
    {
        $name = trim((string) $name);
        if (preg_match('/^бригада\s+(.+)$/iu', $name, $match)) {
            return $match[1];
        }

        return '«'.$name.'»';
    }

    /**
     * «согласовал» → «согласовала», «отметился» → «отметилась», если по отчеству сотрудник — женщина;
     * без отчества — мужской род
     */
    public static function verb(string $verb, ?string $patronymic): string
    {
        $patronymic = mb_strtolower(trim((string) $patronymic));
        if (!preg_match('/(вна|чна|шна|кызы|гызы|кизи|қизи)$/u', $patronymic)) {
            return $verb;
        }

        return str_ends_with($verb, 'ся') ? mb_substr($verb, 0, -2).'ась' : $verb.'а';
    }

    /** «Опоры» → «опоры»; «ВЛИ-0,4» не меняется */
    public static function lower(string $name): string
    {
        $second = mb_substr($name, 1, 1);

        return $second !== '' && mb_strtolower($second) === $second && mb_strtoupper($second) !== $second
            ? mb_strtolower(mb_substr($name, 0, 1)).mb_substr($name, 1)
            : $name;
    }

    public static function upper(string $text): string
    {
        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }
}
