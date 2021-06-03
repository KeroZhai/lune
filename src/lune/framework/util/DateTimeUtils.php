<?php

namespace lune\framework\util;

final class DateTimeUtils
{
    public static function getCurrentDateTimeString(string $format): string
    {
        return date(DateTimeUtils::translateFormat($format));
    }

    private static function translateFormat(string $format): string
    {
        $format = preg_replace("/([YjGgi])/", "\\\\$1", $format);
        $format = preg_replace("/yyyy/", "Y", $format);
        $format = preg_replace("/yy/", "y", $format);
        $format = preg_replace("/MM/", "m", $format);
        $format = preg_replace("/M/", "n", $format);
        $format = preg_replace("/dd/", "temp", $format);
        $format = preg_replace("/d/", "j", $format);
        $format = preg_replace("/temp/", "d", $format);
        $format = preg_replace("/HH/", "H", $format);
        $format = preg_replace("/H/", "G", $format);
        $format = preg_replace("/hh/", "h", $format);
        $format = preg_replace("/h/", "g", $format);
        $format = preg_replace("/hh/", "h", $format);
        $format = preg_replace("/mm/", "i", $format);
        return preg_replace("/ss/", "s", $format);
    }
}