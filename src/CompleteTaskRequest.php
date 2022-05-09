<?php declare(strict_types=1);

namespace App;

use jjok\TodoTwo\Domain\Task\Id as TaskId;
use jjok\TodoTwo\Domain\User\Id as UserId;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CompleteTaskRequest
{
    public static function fromPsr7(Request $psr7request) : self
    {
        return new self($psr7request);
    }

    private function __construct(
        private Request $request
    ) {}

    public function taskId() : TaskId
    {
        return TaskId::fromString($this->request->getAttribute('id'));
    }

    public function userId() : UserId
    {
        $body = json_decode((string) $this->request->getBody(), true);

        return UserId::fromString($body['user']);
    }
}
