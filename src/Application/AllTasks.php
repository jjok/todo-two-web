<?php

namespace App\Application;

use App\Domain\PriorityCalculator;
use DateTimeImmutable as DateTime;
use jjok\TodoTwo\Domain\Task\Projections\AllTasksStorage;

final class AllTasks implements \JsonSerializable
{
    public  function __construct(
        AllTasksStorage $projection,
        PriorityCalculator $priorityCalculator,
        DateTime $now
    ) {
        $this->projection = $projection;
        $this->priorityCalculator = $priorityCalculator;
        $this->now = $now;
    }

    private $projection, $priorityCalculator, $now;

    public function jsonSerialize() : array
    {
        $tasks = array_map(
            [$this, 'formatTask'],
            array_values($this->projection->load())
        );

        $tasks = array_filter($tasks, static function(array $task) : bool {
            return !$task['isArchived'];
        });

        usort($tasks, static function(array $a, array $b) : int {
            return $a['currentPriorityValue'] <=> $b['currentPriorityValue'];
        });

        return array(
            'data' => array_map(static function(array $task) : array {
                unset($task['currentPriorityValue'], $task['isArchived']);

                return $task;
            }, $tasks),
        );
    }

    private function formatTask(array $task) : array
    {
        $calculatedPriority = $this->priorityCalculator->priorityAt(
            $this->now,
            $task['lastCompletedAt'] === null ? null : DateTime::createFromFormat('U', (string) $task['lastCompletedAt']),
            $task['priority']
        );

        return array(
            'id' => $task['id'],
            'name' => $task['name'],
            'priority' => $task['priority'],
            'currentPriority' => $calculatedPriority->toString(),
            'currentPriorityValue' => $calculatedPriority->toFloat(),
            'lastCompletedAt' => $task['lastCompletedAt'],
//            'lastCompletedBy' => $task['lastCompletedBy'],
            'isArchived' => $task['isArchived'],
        );
    }
}
