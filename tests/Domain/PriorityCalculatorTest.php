<?php

namespace Tests\Domain;

use App\Domain\PriorityCalculator;
use PHPUnit\Framework\TestCase;

final class PriorityCalculatorTest extends TestCase
{
    public function priorityProvider() : array
    {
        return [
            [ 25, null, '2019-01-28 23:15:30',  -25.0, 'high'],
            [ 52, null, '2019-01-28 23:15:30',  -52.0, 'high'],
            [100, null, '2019-01-28 23:15:30', -100.0, 'high'],

            // Completed a year ago
            [ 25, '2018-01-28 23:15:30', '2019-01-28 23:15:30', 50.73566717402334 , 'high'],
            [ 52, '2018-01-28 23:15:30', '2019-01-28 23:15:30', 11.726994076836016, 'high'],
            [100, '2018-01-28 23:15:30', '2019-01-28 23:15:30',  3.170979198376459, 'high'],

            // Completed a month ago
            [ 25, '2018-12-28 23:15:30', '2019-01-28 23:15:30', 597.3715651135007 , 'low'   ],
            [ 52, '2018-12-28 23:15:30', '2019-01-28 23:15:30', 138.07589800145632, 'medium'],
            [100, '2018-12-28 23:15:30', '2019-01-28 23:15:30',  37.33572281959379, 'high'  ],

            // Completed a week ago
            [ 25, '2019-01-21 23:15:30', '2019-01-28 23:15:30', 2645.5026455026455 , 'low'   ],
            [ 52, '2019-01-21 23:15:30', '2019-01-28 23:15:30',  611.4789768635923 , 'low'   ],
            [100, '2019-01-21 23:15:30', '2019-01-28 23:15:30',  165.34391534391534, 'medium'],

            // Completed a day ago
            [ 25, '2019-01-27 23:15:30', '2019-01-28 23:15:30', 18518.51851851852  , 'low'],
            [ 52, '2019-01-27 23:15:30', '2019-01-28 23:15:30',  4280.352838045146 , 'low'],
            [100, '2019-01-27 23:15:30', '2019-01-28 23:15:30',  1157.4074074074074, 'low'],

            // Completed right now
            [ 25, '2019-01-28 23:15:30', '2019-01-28 23:15:30', 1600000000000.0   , 'low'],
            [ 52, '2019-01-28 23:15:30', '2019-01-28 23:15:30',  369822485207.1006, 'low'],
            [100, '2019-01-28 23:15:30', '2019-01-28 23:15:30',  100000000000.0   , 'low'],
        ];
    }
    /**
     * @test
     * @dataProvider priorityProvider
     */
    public function it_can_calculate_the_priority_of_a_task(
        int $priority,
        ?string $lastCompleted,
        string $currentTime,
        float $expectedPriority,
        string $expectedPriorityStr
    ) : void {
        $calculator = new PriorityCalculator();

        $currentPriority = $calculator->priorityAt(
            new \DateTimeImmutable($currentTime),
            $lastCompleted === null ? null : new \DateTimeImmutable($lastCompleted),
            $priority
        );

        $this->assertSame($expectedPriority, $currentPriority->toFloat());
        $this->assertSame($expectedPriorityStr, $currentPriority->toString());
    }
}