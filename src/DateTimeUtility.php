<?php

namespace JBuncle\WorkingHours;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/**
 * DateTimeUtility
 *
 * @author jbuncle
 */
final class DateTimeUtility {

    private function __construct() {
        
    }

    public static function createFromInterface(DateTimeInterface $dateTime): DateTime {
        if (method_exists(DateTime::class, 'createFromInterface')) {
            // @phan-suppress-next-line PhanUndeclaredStaticMethod
            return DateTime::createFromInterface($dateTime);
        }

        if ($dateTime instanceof DateTimeImmutable) {
            return DateTime::createFromImmutable($dateTime);
        }

        if ($dateTime instanceof DateTime) {
            return clone $dateTime;
        }

        throw new Exception("Can't create DateTime from instance of " . get_class($dateTime));
    }

    public static function addDays(DateTime $date, int $days) {
        if ($days === 0) {
            return $date;
        }
        if ($days > 0) {
            return $date->add(new DateInterval("P{$days}D"));
        } else {
            $days = abs($days);
            return $date->sub(new DateInterval("P{$days}D"));
        }
    }

    public static function getDayOfWeek(DateTimeInterface $date): int {
        return intval($date->format('N'));
    }

    public static function getHour(DateTimeInterface $dateTime): int {
        return intval($dateTime->format('H'));
    }

    public static function getMinute(DateTimeInterface $dateTime): int {
        return intval($dateTime->format('i'));
    }

    public static function setTime(DateTimeInterface $dateTime, int $hour, int $minute): DateTime {
        $cappedDateTime = DateTimeUtility::createFromInterface($dateTime);
        $cappedDateTime->setTime($hour, $minute);
        return $cappedDateTime;
    }

    public static function isSameDay(DateTimeInterface $dateA, DateTimeInterface $dateB): bool {
        $firstDate = $dateA->format('Y-m-d');
        $secondDate = $dateB->format('Y-m-d');

        return $firstDate === $secondDate;
    }

}
