<?php declare(strict_types=1);

namespace App;

use jjok\TodoTwo\Domain\Task\Commands\ArchiveTask;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ArchiveTaskHandler
{
    public function __construct(
        private ArchiveTask $command
    ) {}

    public function __invoke(Request $request, Response $response) : Response
    {
        try {
            $completeTaskRequest = ArchiveTaskRequest::fromPsr7($request);
            $this->command->execute($completeTaskRequest->taskId());
        }
        //TODO Handle 400s and 500s separately
        catch (\Throwable $e) {
            //TODO Error response
            $response = $response->withStatus(500);
        }

        return $response;
    }
}
