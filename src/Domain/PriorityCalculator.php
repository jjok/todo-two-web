<?php

namespace App\Domain;

final class PriorityCalculator
{
    public function priorityAt(
        \DateTimeImmutable $time,
        ?\DateTimeImmutable $lastCompleted,
        int $priority
    ) : Priority {
        if($lastCompleted === null) {
            return Priority::fromFloat(-1 * $priority);
        }

        $last_completed_at = $lastCompleted->format('U') * 1000;
        $now = $time->format('U') * 1000;
        $seconds_since_completed = $now - $last_completed_at;

        if($seconds_since_completed <= 0) {
            $seconds_since_completed = 1;
        }
        $total = (10 ** 15) / $seconds_since_completed;

        return Priority::fromFloat($total / ($priority ** 2));
    }
}
