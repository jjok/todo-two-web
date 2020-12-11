<?php declare(strict_types=1);

namespace App;

use jjok\TodoTwo\Domain\Task\Id as TaskId;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ArchiveTaskRequest
{
    public static function fromPsr7(Request $psr7request) : self
    {
        $request = new self();
        $request->request = $psr7request;

        return $request;
    }

    /** @var Request */
    private $request;

    public function taskId() : TaskId
    {
        return TaskId::fromString($this->request->getAttribute('id'));
    }
}
