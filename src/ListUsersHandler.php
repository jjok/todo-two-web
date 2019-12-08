<?php declare(strict_types=1);

namespace App;

use App\Application\Users;
use jjok\TodoTwo\Domain\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListUsersHandler
{
    public function __construct(Users $users)
    {
        $this->users = $users;
    }

    private $users;

    public function __invoke(Request $request, Response $response): Response
    {
        $response->getBody()->write(json_encode(array(
            'data' => array_map(static function(User $user) {
                return array(
                    'id' => $user->id()->toString(),
                    'name' => $user->name(),
                );
            }, $this->users->all()),
        )));

        return $response->withHeader('Content-type', 'application/json');
    }
}
