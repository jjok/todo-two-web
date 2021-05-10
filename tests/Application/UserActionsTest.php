<?php

namespace Tests\Application;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request as SlimRequest;
use Slim\Psr7\Uri;

final class UserActionsTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();

        $this->app = AppFactory::create();

        $this->app->addRoutingMiddleware();

        $routes = require __DIR__ . '/../../app/routes.php';
        $routes($this->app);
    }

    private $app;

    /** @test */
    public function the_registered_users_can_be_listed() : void
    {
        $this->assertUsersEqual([
            array(
                'id' => '12345678-1234-5678-1234-1234567890ab',
                'name' => 'Some User',
            ),
        ]);
    }

    private function assertUsersEqual(array $expectedUsers) : void
    {
        $request = $this->createRequest('GET', '/users');

        $response = $this->app->handle($request);

        $responseBody = json_decode((string) $response->getBody(), true);

        $actualUsers = $responseBody['data'];

        self::assertCount(count($expectedUsers), $actualUsers);
        foreach ($actualUsers as $n => $user) {
            self::assertSame($expectedUsers[$n]['id']      , $user['id']);
            self::assertSame($expectedUsers[$n]['name']    , $user['name']);
        }
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
