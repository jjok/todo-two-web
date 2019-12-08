<?php

namespace App\Application;

use jjok\TodoTwo\Domain\User;
use jjok\TodoTwo\Domain\User\Id as UserId;

final class Users
{
    public static function fromJson(string $file) : self
    {
        $optionsJson = file_get_contents($file);
        $options = json_decode($optionsJson, true);
        $users = array_map(static function(array $user) : User {
            return new User(
                UserId::fromString($user['id']),
                $user['name']
            );
        }, $options['users']);

        return new self(...$users);
    }

    private function __construct(User ...$users)
    {
        $this->users = $users;
    }

    private $users;

    /**
     * @return User[]
     */
    public function all() : array
    {
        return $this->users;
    }
}
