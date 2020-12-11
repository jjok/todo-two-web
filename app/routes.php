<?php declare(strict_types=1);

use jjok\TodoTwo\Domain\ProjectionBuildingEventStore;
use jjok\TodoTwo\Domain\Task\Commands\CreateTask;
use jjok\TodoTwo\Domain\Task\Query\GetById as GetTaskById;
use jjok\TodoTwo\Domain\User\Query\GetUserById;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

$injector = require __DIR__ . '/dependencies.php';

$eventStore = $injector->make(ProjectionBuildingEventStore::class);
$getTaskById = $injector->make(GetTaskById::class);
$getUserById = $injector->make(GetUserById::class);
$allTasks = $injector->make(\App\Application\AllTasks::class);


return function (App $app) use ($injector, $eventStore, $allTasks, $getTaskById, $getUserById) {
//    $container = $app->getContainer();

//    $app->get('/', function (Request $request, Response $response) {
//        $response->getBody()->write('Hello world!');
//        return $response;
//    });

    $app->get('/tasks', function(Request $request, Response $response) use ($allTasks) {
        $response->getBody()->write(json_encode($allTasks));

        return $response->withHeader('Content-type', 'application/json');
    });

    $app->put('/tasks/{id}', function(Request $request, Response $response) use ($eventStore) {
//        try {
            $createTask = new CreateTask($eventStore);

            $createTaskRequest = \App\CreateTaskRequest::fromPsr7($request);

            $createTask->execute($createTaskRequest->id(), $createTaskRequest->name(), $createTaskRequest->priority());

            $response = $response->withStatus(201);
//        }
//        catch (Throwable $e) {
            //TODO Error response
//            $response = $response->withStatus(500);
//        }

        return $response;
    });

    $app->patch('/tasks/{id}', function(Request $request, Response $response) use ($eventStore, $getTaskById) {
        try {
//            $renameTask = new RenameTask($eventStore, $getTaskById);
////            $changeTaskPriority = new ChangeTaskPriority($eventStore, $getTaskById);
//
//            $renameTaskRequest = RenameTaskRequest::fromPsr7($request);
//
//            $renameTask->execute($renameTaskRequest->id(), $renameTaskRequest->newName());
        }
        catch (Throwable $e) {
            //TODO Error response
            $response = $response->withStatus(500);
        }

        return $response;
    });

    $app->post('/tasks/{id}/complete', $injector->make(\App\CompleteTaskHandler::class));

    $app->post('/tasks/{id}/archive', $injector->make(\App\ArchiveTaskHandler::class));

//    $app->group('/users', function (Group $group) use ($container) {
//        $group->get('', ListUsersAction::class);
//        $group->get('/{id}', ViewUserAction::class);
//    });

    $app->get('/users', $injector->make(\App\ListUsersHandler::class));
};
