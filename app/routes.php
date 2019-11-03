<?php declare(strict_types=1);

//use App\Application\Actions\User\ListUsersAction;
//use App\Application\Actions\User\ViewUserAction;
use jjok\TodoTwo\Domain\ProjectionBuildingEventStore;
use jjok\TodoTwo\Domain\Task\Commands\CompleteTask;
use jjok\TodoTwo\Domain\Task\Commands\CreateTask;
use jjok\TodoTwo\Domain\Task\Query\GetById as GetTaskById;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
//use Slim\Interfaces\RouteCollectorProxyInterface as Group;

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


final class AllTasks implements \JsonSerializable
{
    public  function __construct(
        jjok\TodoTwo\Domain\Task\Projections\AllTasksStorage $projection,
        \App\Domain\PriorityCalculator $priorityCalculator,
        DateTimeImmutable $now
    ) {
        $this->projection = $projection;
        $this->priorityCalculator = $priorityCalculator;
        $this->now = $now;
    }

    private $projection, $priorityCalculator, $now;

    public function jsonSerialize() : array
    {
        $tasks = array_map(
            [$this, 'formatTask'],
            array_values($this->projection->load())
        );

        usort($tasks, static function(array $a, array $b) : int {
            return $a['currentPriorityValue'] <=> $b['currentPriorityValue'];
        });

        return array(
            'data' => array_map(static function(array $task) : array {
                unset($task['currentPriorityValue']);

                return $task;
            }, $tasks),
        );
    }

    private function formatTask(array $task) : array
    {
        $calculatedPriority = $this->priorityCalculator->priorityAt(
            $this->now,
            $task['lastCompletedAt'] === null ? null : \DateTimeImmutable::createFromFormat('U', (string) $task['lastCompletedAt']),
            $task['priority']
        );

        return array(
            'id' => $task['id'],
            'name' => $task['name'],
            'priority' => $task['priority'],
            'currentPriority' => $calculatedPriority->toString(),
            'currentPriorityValue' => $calculatedPriority->toFloat(),
            'lastCompletedAt' => $task['lastCompletedAt'],
//            'lastCompletedBy' => $task['lastCompletedBy'],
        );
    }
}



$injector = require __DIR__ . '/dependencies.php';

$eventStore = $injector->make(ProjectionBuildingEventStore::class);
$getTaskById = $injector->make(GetTaskById::class);
$allTasks = $injector->make(AllTasks::class);


return function (App $app) use ($eventStore, $allTasks, $getTaskById) {
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

        try {
            $completeTask->execute((string) $request->getAttribute('id'), (string) $body['by']);
        }
        catch (Throwable $e) {
            //TODO Error response
            $response = $response->withStatus(500);
        }

        return $response;
    });

//    $app->group('/users', function (Group $group) use ($container) {
//        $group->get('', ListUsersAction::class);
//        $group->get('/{id}', ViewUserAction::class);
//    });
};
