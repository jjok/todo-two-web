<?php

namespace App\Domain;

final class Priority
{
    public static function fromFloat(float $value) : self
    {
        return new self($value);
    }

    private function __construct(
        private float $value
    ) {}

    public function toFloat() : float
    {
        return $this->value;
    }

    public function toString() : string
    {
        if($this->toFloat() < 100) {
            return 'high';
        }
        if($this->toFloat() < 250) {
            return 'medium';
        }

        return 'low';
    }
}
