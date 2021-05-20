<?php declare(strict_types=1);

namespace App;

use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateTaskRequest
{
    public static function fromPsr7(Request $request) : self
    {
        $body = json_decode((string) $request->getBody(), true);

        $id = (string) $request->getAttribute('id');
        $name = isset($body['name']) ? (string) $body['name'] : null;
        $priority = isset($body['priority']) ? (int) $body['priority'] : null;

        return new self($id, $name, $priority);
    }

    private function __construct(string $id, ?string $maybeName, ?int $maybePriority)
    {
        $this->id = $id;
        $this->maybeName = $maybeName;
        $this->maybePriority = $maybePriority;
    }

    private string $id;
    private ?string $maybeName;
    private ?int $maybePriority;

    public function id() : string
    {
        return $this->id;
    }

    public function requiresNameChange() : bool
    {
        return $this->maybeName !== null;
    }

    public function newName() : ?string
    {
        return $this->maybeName;
    }

    public function requiresPriorityChange() : bool
    {
        return $this->maybePriority !== null;
    }

    public function newPriority() : ?int
    {
        return $this->maybePriority;
    }
}
