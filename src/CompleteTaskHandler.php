<?php declare(strict_types=1);

namespace App;

use jjok\TodoTwo\Domain\Task\Commands\CompleteTask;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CompleteTaskHandler
{
    public function __construct(CompleteTask $completeTask)
    {
        $this->command = $completeTask;
    }

    private $command;

    public function __invoke(Request $request, Response $response) : Response
    {
        try {
            $completeTaskRequest = CompleteTaskRequest::fromPsr7($request);
            $this->command->execute(
                $completeTaskRequest->taskId(),
                $completeTaskRequest->userId()
            );
        }
            //TODO Handle 400s and 500s separately
        catch (\Throwable $e) {
            //TODO Error response
            $response = $response->withStatus(500);
        }

        return $response;
    }
}
