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

        $app = AppFactory::create();

        $app->addRoutingMiddleware();

        $routes = require __DIR__ . '/../../app/routes.php';
        $routes($app);

        $this->app = $app;

        touch(__DIR__ . '/../data/events.dat');
        touch(__DIR__ . '/../data/tasks.json');

        file_put_contents(__DIR__ . '/../data/events.dat', '');
        file_put_contents(__DIR__ . '/../data/tasks.json', '');
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

        $response = $this->app->handle(
            $this->createTaskRequest('2c17bd45-d905-45cb-803a-d392735d40e8', 'New task', 50)
        );
        $this->assertSame(201, $response->getStatusCode());

        $this->assertTasksEqual([array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'lastCompletedAt' => null,
            'lastCompletedBy' => null,
        )]);

        $response = $this->app->handle(
            $this->createTaskRequest('2c17bd45-d905-45cb-803a-d392735d40e9', 'New task 2', 30)
        );
        $this->assertSame(201, $response->getStatusCode());

        $this->assertTasksEqual([array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'lastCompletedAt' => null,
            'lastCompletedBy' => null,
        ), array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e9',
            'name' => 'New task 2',
            'priority' => 30,
            'lastCompletedAt' => null,
            'lastCompletedBy' => null,
        )]);

        $time1 = time();
        $response = $this->app->handle(
            $this->completeTaskRequest('2c17bd45-d905-45cb-803a-d392735d40e9', 'Jonathan')
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->assertTasksEqual([array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'lastCompletedAt' => null,
            'lastCompletedBy' => null,
        ), array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e9',
            'name' => 'New task 2',
            'priority' => 30,
            'lastCompletedAt' => $time1, //FIXME Dubious
            'lastCompletedBy' => 'Jonathan',
        )]);

        $time2 = time();
        $response = $this->app->handle(
            $this->completeTaskRequest('2c17bd45-d905-45cb-803a-d392735d40e8', 'Someone Else')
        );

        $this->assertSame(200, $response->getStatusCode());

        $this->assertTasksEqual([array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e8',
            'name' => 'New task',
            'priority' => 50,
            'lastCompletedAt' => $time2, //FIXME Dubious
            'lastCompletedBy' => 'Someone Else',
        ), array(
            'id' => '2c17bd45-d905-45cb-803a-d392735d40e9',
            'name' => 'New task 2',
            'priority' => 30,
            'lastCompletedAt' => $time1, //FIXME Dubious
            'lastCompletedBy' => 'Jonathan',
        )]);
    }

    private function assertTasksEqual(array $expectedTasks) : void
    {
        $request = $this->createRequest('GET', '/tasks');

        $response = $this->app->handle($request);

        $this->assertSame($expectedTasks, json_decode((string) $response->getBody(), true));
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
        $request->getBody()->write(json_encode(array('by' => $userName)));

        return $request;
    }

    protected function createRequest(
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
