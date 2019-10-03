<?php declare(strict_types=1);

//use App\Application\Actions\User\ListUsersAction;
//use App\Application\Actions\User\ViewUserAction;
use jjok\TodoTwo\Domain\ProjectionBuildingEventStore;
use jjok\TodoTwo\Domain\Task\Commands\ChangeTaskPriority;
use jjok\TodoTwo\Domain\Task\Commands\CompleteTask;
use jjok\TodoTwo\Domain\Task\Commands\RenameTask;
use jjok\TodoTwo\Domain\Task\Projections\AllTasksProjector;
use jjok\TodoTwo\Domain\Task\Query\GetById as GetTaskById;
use jjok\TodoTwo\Infrastructure\File\AllTasksStorage;
use jjok\TodoTwo\Infrastructure\File\EventStream;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
//use Slim\Interfaces\RouteCollectorProxyInterface as Group;

use jjok\TodoTwo\Domain\Task\Commands\CreateTask;
use jjok\TodoTwo\Infrastructure\File\EventStore;

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

//$dataDir = __DIR__ . '/../data/';
//TODO make this configurable
$dataDir = '/data/';
if(isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'test') {
    $dataDir = __DIR__ . '/../tests/data/';
}

$eventStoreFileName = $dataDir . 'events.dat';
$eventStoreFile = new SplFileObject($eventStoreFileName, 'a+');

$allTasksProjectionFileName = $dataDir . 'tasks.json';
$projection = new AllTasksStorage($allTasksProjectionFileName);

$eventStore = new ProjectionBuildingEventStore(
    new EventStore($eventStoreFile),
    new AllTasksProjector($projection)
);

$eventStream = new EventStream($eventStoreFile);
$getTaskById = new GetTaskById($eventStream);

return function (App $app) use ($eventStore, $projection, $getTaskById) {
//    $container = $app->getContainer();

//    $app->get('/', function (Request $request, Response $response) {
//        $response->getBody()->write('Hello world!');
//        return $response;
//    });

    $app->get('/tasks', function(Request $request, Response $response) use ($projection) {
        $response->getBody()->write(json_encode(array_values($projection->load())));

        return $response->withHeader('Content-type', 'application/json');
    });

    $app->put('/tasks/{id}', function(Request $request, Response $response) use ($eventStore) {
//        try {
            $createTask = new CreateTask($eventStore);

            $createTaskRequest = CreateTaskRequest::fromPsr7($request);

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

    $app->post('/tasks/{id}/complete', function(Request $request, Response $response) use ($eventStore, $getTaskById) {

        $completeTask = new CompleteTask($eventStore, $getTaskById);

        $body = json_decode((string) $request->getBody(), true);

//        print_r($body);
//        print_r($request->getAttribute('id'));

        $completeTask->execute((string) $request->getAttribute('id'), (string) $body['by']);

        return $response;
    });

//    $app->group('/users', function (Group $group) use ($container) {
//        $group->get('', ListUsersAction::class);
//        $group->get('/{id}', ViewUserAction::class);
//    });
};
