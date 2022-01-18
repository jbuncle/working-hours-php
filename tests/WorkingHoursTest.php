<?php

namespace JBuncle\WorkingHours;

use DateTime;
use PHPUnit\Framework\TestCase;

final class WorkingHoursTest extends TestCase {

    private WorkingHours $dateUtil;

    protected function setUp(): void {
        parent::setUp();

        $this->dateUtil = new WorkingHours(9, 0, 17, 30);
    }

    /**
     * 
     * @dataProvider workingHoursTestCases
     * @param 
     * 
     */
    public function testGetWorkingHours(DateTime $startDate, DateTime $endDate, float $expected) {

        $actual = $this->dateUtil->getWorkingHours($startDate, $endDate);

        $this->assertSame($expected, $actual);
    }

    public function workingHoursTestCases() {

        return [
            // Weekends
            [new DateTime("2022-01-14T17:30"), new DateTime("2022-01-17T09:00"), 0],
            [new DateTime("2022-01-14T17:30"), new DateTime("2022-01-17T17:30"), 8.5],
            [new DateTime("2022-01-14T09:00"), new DateTime("2022-01-17T17:30"), 17],
            // 2 working weeks
            [new DateTime("2022-01-10T09:00"), new DateTime("2022-01-21T17:30"), 85],
            // 1 working week
            [new DateTime("2022-01-10T09:00"), new DateTime("2022-01-17T09:00"), 42.5],
            //
            [new DateTime("2022-01-20T09:00"), new DateTime("2022-01-21T17:30"), 17],
            // Thurs - Teu
            [new DateTime("2022-01-13T09:00"), new DateTime("2022-01-18T17:30"), 34],
            // Single hours
            [new DateTime("2022-01-20T09:00"), new DateTime("2022-01-20T10:00"), 1],
            [new DateTime("2022-01-20T16:30"), new DateTime("2022-01-20T17:30"), 1],
            [new DateTime("2022-01-20T17:30"), new DateTime("2022-01-21T10:00"), 1],
            [new DateTime("2022-01-20T16:30"), new DateTime("2022-01-21T09:00"), 1],
            // Half day
            [new DateTime("2022-01-20T09:00"), new DateTime("2022-01-20T12:30"), 3.5],
            // 0 Hours
            [new DateTime("2022-01-20T17:30"), new DateTime("2022-01-21T09:00"), 0],
        ];
    }

}
