<?php declare(strict_types=1);

namespace App;

use jjok\TodoTwo\Domain\Task\Commands\ChangeTaskPriority;
use jjok\TodoTwo\Domain\Task\Commands\RenameTask;
use jjok\TodoTwo\Domain\Task\Id as TaskId;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class UpdateTaskHandler
{
    public function __construct(ChangeTaskPriority $changeTaskPriority, RenameTask $renameTask)
    {
        $this->changeTaskPriority = $changeTaskPriority;
        $this->renameTask = $renameTask;
    }

    private $changeTaskPriority;
    private $renameTask;

    public function __invoke(Request $request, Response $response) : Response
    {
        try {
            $updateTaskRequest = UpdateTaskRequest::fromPsr7($request);
            if($updateTaskRequest->requiresNameChange()) {
                $this->renameTask->execute(
                    TaskId::fromString($updateTaskRequest->id()),
                    $updateTaskRequest->newName()
                );
            }
            if($updateTaskRequest->requiresPriorityChange()) {
                $this->changeTaskPriority->execute(
                    TaskId::fromString($updateTaskRequest->id())->toString(),
                    $updateTaskRequest->newPriority()
                );
            }

            $response = $response->withStatus(204);
        }
        //TODO Handle 400s and 500s separately
        catch (\Throwable $e) {
            //TODO Error response
            $response = $response->withStatus(500);
        }

        return $response;
    }
}
