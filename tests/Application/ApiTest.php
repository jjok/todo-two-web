<?php

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

final class ApiTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app = AppFactory::create();

        $this->app->addRoutingMiddleware();

        $routes = require __DIR__ . '/../../app/routes.php';
        $routes($this->app);

//        touch(__DIR__ . '/../data/events.dat');
//        touch(__DIR__ . '/../data/tasks.json');
//
//        file_put_contents(__DIR__ . '/../data/events.dat', '');
//        file_put_contents(__DIR__ . '/../data/tasks.json', '');
    }

    private $app;

    public function tearDown()
    {
        unlink(__DIR__ . '/../data/events.dat');
        unlink(__DIR__ . '/../data/tasks.json');

        parent::tearDown();
    }

    /** @test */
    public function everything_works_ok() : void
    {
        $this->assertTasksEqual([]);

        $this->createTask('2c17bd45-d905-45cb-803a-d392735d40e8', 'New task', 50);

        $this->assertTasksEqual([array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'currentPriority' => 'high',
            'lastCompletedAt' => null,
//            'lastCompletedBy' => null,
        )]);

        $this->createTask('2c17bd45-d905-45cb-803a-d392735d40e9', 'New task 2', 60);

        $this->assertTasksEqual([ array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e9',
            'name' => 'New task 2',
            'priority' => 60,
            'currentPriority' => 'high',
            'lastCompletedAt' => null,
//            'lastCompletedBy' => null,
        ), array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'currentPriority' => 'high',
            'lastCompletedAt' => null,
//            'lastCompletedBy' => null,
        )]);

        $time1 = time();
        $response = $this->app->handle(
            $this->completeTaskRequest('2c17bd45-d905-45cb-803a-d392735d40e9', '00e0c19f-f5bc-4718-b368-d157bb3a98c5')
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->assertTasksEqual([array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'currentPriority' => 'high',
            'lastCompletedAt' => null,
//            'lastCompletedBy' => null,
        ), array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e9',
            'name' => 'New task 2',
            'priority' => 60,
            'currentPriority' => 'low',
            'lastCompletedAt' => $time1, //FIXME Dubious
//            'lastCompletedBy' => 'Jonathan',
        )]);

        $time2 = time();
        $response = $this->app->handle(
            $this->completeTaskRequest('2c17bd45-d905-45cb-803a-d392735d40e8', '00e0c19f-f5bc-4718-b368-d157bb3a98c5')
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->assertTasksEqual([array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e9',
            'name' => 'New task 2',
            'priority' => 60,
            'currentPriority' => 'low',
            'lastCompletedAt' => $time1, //FIXME Dubious
//            'lastCompletedBy' => 'Jonathan',
        ), array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'currentPriority' => 'low',
            'lastCompletedAt' => $time2, //FIXME Dubious
//            'lastCompletedBy' => 'Someone Else',
        )]);
    }

    private function createTask(string $id, string $name, int $priority) : void
    {
        $response = $this->app->handle(
            $this->createTaskRequest($id, $name, $priority)
        );
        $this->assertSame(201, $response->getStatusCode());
    }

    private function assertTasksEqual(array $expectedTasks) : void
    {
        $request = $this->createRequest('GET', '/tasks');

        $response = $this->app->handle($request);

        $responseBody = json_decode((string) $response->getBody(), true);

        $actualTasks = $responseBody['data'];

        $this->assertCount(count($expectedTasks), $actualTasks);
        foreach ($actualTasks as $n => $task) {
            $this->assertSame($expectedTasks[$n]['id']      , $task['id']);
            $this->assertSame($expectedTasks[$n]['name']    , $task['name']);
            $this->assertSame($expectedTasks[$n]['priority'], $task['priority']);
            $this->assertSame($expectedTasks[$n]['currentPriority'], $task['currentPriority']);
            $this->assertSame($expectedTasks[$n]['lastCompletedAt'], $task['lastCompletedAt']);
//            $this->assertSame($expectedTasks[$n]['lastCompletedBy'], $task['lastCompletedBy']);
        }
    }

    private function createTaskRequest(string $id, string $name, int $priority) : Request
    {
        $request = $this->createRequest('PUT', sprintf('/tasks/%s', $id));
        $request = $request->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode(array('name' => $name, 'priority' => $priority)));

        return $request;
    }

    private function completeTaskRequest(string $id, string $userName) : Request
    {
        $request = $this->createRequest('POST', sprintf('/tasks/%s/complete', $id));
        $request = $request->withHeader('Content-Type', 'application/json');
        $request->getBody()->write(json_encode(array('user' => $userName)));

        return $request;
    }

    private function createRequest(
        string $method,
        string $path,
        array $headers = ['HTTP_ACCEPT' => 'application/json'],
        array $serverParams = [],
        array $cookies = []
    ): Request {
        $uri = new Uri('', '', 80, $path);
        $handle = fopen('php://temp', 'w+');
        $stream = (new StreamFactory())->createStreamFromResource($handle);

        $h = new Headers();
        foreach ($headers as $name => $value) {
            $h->addHeader($name, $value);
        }

        return new SlimRequest($method, $uri, $h, $serverParams, $cookies, $stream);
    }
}
