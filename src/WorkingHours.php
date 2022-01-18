<?php

namespace JBuncle\WorkingHours;

use DateTime;
use DateTimeInterface;
use Exception;

/**
 * WorkingHours
 *
 * @author jbuncle
 */
final class WorkingHours {

    private const DAYOFWEEK_SAT = 6;
    private const DAYOFWEEK_SUN = 7;

    private int $copHour = 17;
    private int $copMinutes = 30;
    private int $sopHour = 9;
    private int $sopMinutes = 0;

    public function __construct(
            int $sopHour, int $sopMinutes,
            int $copHour, int $copMinutes
    ) {
        $this->copHour = $copHour;
        $this->copMinutes = $copMinutes;
        $this->sopHour = $sopHour;
        $this->sopMinutes = $sopMinutes;
    }

    public function getWorkingHours(
            DateTimeInterface $startDateTime,
            DateTimeInterface $endDateTime
    ): float {
        $startDateTime = DateTimeUtility::createFromInterface($startDateTime);
        $endDateTime = DateTimeUtility::createFromInterface($endDateTime);

        if ($startDateTime->getTimestamp() > $endDateTime->getTimestamp()) {
            throw new Exception("Start DateTime is after end");
        }


        // Check duration was same day, just allow it
        if (DateTimeUtility::isSameDay($startDateTime, $endDateTime)) {
            // Same day 
            $diffMinutes = $this->diffTotalMinutes($startDateTime, $endDateTime);
            $diffHours = $diffMinutes / 60;
            return $diffHours;
        }

        // Duration spans more than one day, so exclude non-working hours
        // Fix dates to within bounds
        $startDateTime = $this->limitTimeBounds($startDateTime, $this->sopHour, $this->sopMinutes, $this->copHour, $this->copMinutes);
        $endDateTime = $this->limitTimeBounds($endDateTime, $this->sopHour, $this->sopMinutes, $this->copHour, $this->copMinutes);

        $daysBetween = $this->getWholeWorkingDaysBetween($startDateTime, $endDateTime);
        if ($daysBetween < 1) {
            // Not same day but no full day between
            $startDayDiffHours = $this->diffToTimeMinutes($startDateTime, $this->copHour, $this->copMinutes) / 60;
            $endDayDiffHours = $this->diffFromTimeMinutes($endDateTime, $this->sopHour, $this->sopMinutes) / 60;
            $total = $startDayDiffHours + $endDayDiffHours;
        } else {
            // Different days
            $startDayDiffHours = $this->diffToTimeMinutes($startDateTime, $this->copHour, $this->copMinutes) / 60;
            $endDayDiffHours = $this->diffFromTimeMinutes($endDateTime, $this->sopHour, $this->sopMinutes) / 60;
            $workingDayHours = $this->diffHours($this->sopHour, $this->sopMinutes, $this->copHour, $this->copMinutes);

            $workingHoursBetween = $daysBetween * $workingDayHours;

            $total = $startDayDiffHours + $workingHoursBetween + $endDayDiffHours;
        }
        return $total;
    }

    /**
     * Get the number of working days between the 2 days (exclusive of the end date)
     *
     * @param DateTime $startDateTime
     * @param DateTime $endDateTime
     *
     * @return int
     */
    private function getWholeWorkingDaysBetween(DateTime $startDateTime, DateTime $endDateTime): int {
        $startDateTime = DateTimeUtility::setTime($startDateTime, 0, 0);
        $endDateTime = DateTimeUtility::setTime($endDateTime, 0, 0);

        if ($endDateTime->getTimestamp() < $startDateTime->getTimestamp()) {
            return 0;
        }

        $startDateTime = $this->nextWorkingDay($startDateTime);
        if (DateTimeUtility::isSameDay($startDateTime, $endDateTime) || $startDateTime->getTimestamp() >= $endDateTime->getTimestamp()) {
            return 0;
        }

        $days = 0;
        do {
            $startDateTime = $this->nextWorkingDay($startDateTime);
            $days++;
        } while (!DateTimeUtility::isSameDay($startDateTime, $endDateTime) && $startDateTime->getTimestamp() <= $endDateTime->getTimestamp());

        return $days;
    }

    private function nextWorkingDay(DateTime $date): DateTime {
        $date = DateTimeUtility::addDays($date, 1);

        while (!$this->isWorkingDay($date)) {
            $date = DateTimeUtility::addDays($date, 1);
        }

        return $date;
    }

    private function isWorkingDay(DateTime $date): bool {
        $dayOfWeek = DateTimeUtility::getDayOfWeek($date);

        return !($dayOfWeek === self::DAYOFWEEK_SAT || $dayOfWeek === self::DAYOFWEEK_SUN);
    }

    private function diffTotalMinutes(DateTimeInterface $startDateTime, DateTimeInterface $endDateTime): float {
        return ($endDateTime->getTimestamp() - $startDateTime->getTimestamp() ) / 60;
    }

    private function diffHours(int $hourA, int $minuteA, int $hourB, int $minuteB): float {
        $totalMinutesA = ($hourA * 60) + $minuteA;
        $totalMinutesB = ($hourB * 60) + $minuteB;

        return ($totalMinutesB - $totalMinutesA) / 60;
    }

    private function diffToTimeMinutes(
            DateTimeInterface $startDateTime,
            int $hour,
            int $minute
    ): float {
        $endDateTime = DateTimeUtility::setTime($startDateTime, $hour, $minute);
        return $this->diffTotalMinutes($startDateTime, $endDateTime);
    }

    private function diffFromTimeMinutes(
            DateTimeInterface $endDateTime,
            int $hour,
            int $minute
    ): float {
        $startTime = DateTimeUtility::setTime($endDateTime, $hour, $minute);
        return $this->diffTotalMinutes($startTime, $endDateTime);
    }

    private function limitTimeBounds(
            DateTime $dateTime,
            int $lowerHours, int $lowerMinutes,
            int $upperHours, int $upperMinutes
    ): DateTime {
        $dateTime = $this->limitLowerBounds($dateTime, $lowerHours, $lowerMinutes);
        $dateTime = $this->limitUpperBounds($dateTime, $upperHours, $upperMinutes);
        return $dateTime;
    }

    /**
     * Limit hours and minutes to given bounds.
     *
     * @param DateTime $dateTime
     * @param int $hoursLimit
     * @param int $minutesLimit
     *
     * @return DateTime
     */
    private function limitLowerBounds(DateTime $dateTime, int $hoursLimit, int $minutesLimit): DateTime {
        $hours = DateTimeUtility::getHour($dateTime);
        $minute = DateTimeUtility::getMinute($dateTime);

        if ($hours > $hoursLimit || ($hours == $hoursLimit && $minute >= $minutesLimit)) {
            return $dateTime;
        }

        return DateTimeUtility::setTime($dateTime, $hoursLimit, $minutesLimit);
    }

    /**
     * Limit upper bounds to given hours and minutes.
     *
     * @param DateTimeInterface $dateTime
     * @param int $hoursLimit
     * @param int $minutesLimit
     *
     * @return DateTime
     */
    private function limitUpperBounds(
            DateTimeInterface $dateTime,
            int $hoursLimit,
            int $minutesLimit
    ): DateTime {
        $hours = DateTimeUtility::getHour($dateTime);
        $minute = DateTimeUtility::getMinute($dateTime);

        if ($hours < $hoursLimit || ($hours == $hoursLimit && $minute < $minutesLimit)) {
            return $dateTime;
        }
        // Cap the DateTime
        return DateTimeUtility::setTime($dateTime, $hoursLimit, $minutesLimit);
    }

}
