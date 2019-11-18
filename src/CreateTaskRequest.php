<?php declare(strict_types=1);

namespace App;

use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateTaskRequest
{
    public static function fromPsr7(Request $request) : self
    {
        $body = json_decode((string) $request->getBody(), true);

        return new self(
            (string) $request->getAttribute('id'),
            (string) $body['name'],
            (int) $body['priority']
        );
    }

    private function __construct(string $id, string $name, int $priority)
    {
        $this->id = $id;
        $this->name = $name;
        $this->priority = $priority;
    }

    private $id, $name, $priority;

    public function id() : string
    {
        return $this->id;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function priority() : int
    {
        return $this->priority;
    }
}
