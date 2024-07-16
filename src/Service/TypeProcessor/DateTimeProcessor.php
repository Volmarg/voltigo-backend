<?php

namespace App\Service\TypeProcessor;

use Exception;

/**
 * Provides variety of logic for handling handling:
 * - time,
 * - date time,
 * - date,
 */
class DateTimeProcessor
{
    /**
     * Represents the time in form of accumulated minutes,
     * this format is useful for easier time comparison
     *
     * Example:
     * - 01:15:50 equal 75.5,
     * - 12:20:00 equal 780,
     *
     * @param string $time (h:i:s)
     *
     * @return float
     *
     * @throws Exception
     */
    public static function timeIntoMinutes(string $time): float
    {
        $timePartials = explode(":", $time);
        if (count($timePartials) != 3) {
            throw new Exception("Wrong time format given, expected `h:i:s` got time of value: {$time}");

        }
        $hour    = (float)$timePartials[0];
        $minutes = (float)$timePartials[1];
        $seconds = (float)$timePartials[2];

        $timeMinutes = ($hour * 60) + $minutes + ($seconds / 60);

        return $timeMinutes;
    }

    /**
     * - Takes time in format: "h:i:s",
     * - Checks how much time is left toward that hour/minutes/second,
     * - returns formatted string that represents countdown
     *
     * @param string $hourMinuteSeconds
     *
     * @return string
     *
     * @throws Exception
     */
    public static function countdownFormatTillTime(string $hourMinuteSeconds): string
    {
        $nowTime          = (new \DateTime())->format("H:i:s");
        $nowMinutes       = DateTimeProcessor::timeIntoMinutes($nowTime);
        $endTimeMinutes   = DateTimeProcessor::timeIntoMinutes($hourMinuteSeconds);

        $minutesLeft = $endTimeMinutes - $nowMinutes;
        if (($endTimeMinutes - $nowMinutes) <= 0) {
            return "0 sec";
        }

        $hourPart = date('H', mktime(0, 0, $minutesLeft * 60));
        $hourPart = ($hourPart != "00") ? ($hourPart . "h ") : "";

        $minPart = date('i', mktime(0, 0, $minutesLeft * 60));
        $minPart = ($minPart != "00") ? ($minPart . "min ") : "";

        $secPart = date('s', mktime(0, 0, $minutesLeft * 60));
        $secPart = ($secPart != "00") ? ($secPart . "sec ") : "";

        // seconds provide better accuracy
        return $hourPart . $minPart . $secPart;
    }
}