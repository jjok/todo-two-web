<?php declare(strict_types=1);

//use DI\ContainerBuilder;
//use Monolog\Handler\StreamHandler;
//use Monolog\Logger;
//use Monolog\Processor\UidProcessor;
//use Psr\Container\ContainerInterface;
//use Psr\Log\LoggerInterface;

//return function (ContainerBuilder $containerBuilder) {
//    $containerBuilder->addDefinitions([
//        LoggerInterface::class => function (ContainerInterface $c) {
//            $settings = $c->get('settings');
//
//            $loggerSettings = $settings['logger'];
//            $logger = new Logger($loggerSettings['name']);
//
//            $processor = new UidProcessor();
//            $logger->pushProcessor($processor);
//
//            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
//            $logger->pushHandler($handler);
//
//            return $logger;
//        },
//    ]);
//};


use jjok\TodoTwo\Domain;
use jjok\TodoTwo\Domain\ProjectionBuildingEventStore;
use jjok\TodoTwo\Domain\Task\Projections\AllTasksProjector;
use jjok\TodoTwo\Infrastructure;

function dataDir(string $env) : string {
    if($env === 'hassio') {
        return '/data/';
    }
    if($env === 'test') {
        return __DIR__ . '/../tests/data/';
    }

    return __DIR__ . '/../data/';
}

$injector = new Auryn\Injector();

$injector->delegate(\SplFileObject::class, function() {
    $dataDir = dataDir((string) getenv('APP_ENV'));
    $eventStoreFileName = $dataDir . 'events.dat';

    return new SplFileObject($eventStoreFileName, 'a+');
});
$injector->share(\SplFileObject::class);

$dataDir = dataDir((string) getenv('APP_ENV'));
$eventStoreFile = $injector->make(\SplFileObject::class);

$allTasksProjectionFileName = $dataDir . 'tasks.json';
$optionsFileName = $dataDir . 'options.json';


$injector->defineParam('filename', $allTasksProjectionFileName);

$injector->share(Domain\EventStream::class);
$injector->share(Domain\EventStore::class);

$injector->alias(Domain\EventStore::class, ProjectionBuildingEventStore::class);
$injector->alias(Domain\EventStream::class, Infrastructure\File\EventStream::class);
$injector->alias(Domain\Task\Projections\AllTasksStorage::class, Infrastructure\File\AllTasksStorage::class);

$injector->delegate(ProjectionBuildingEventStore::class, function(Auryn\Injector $injector) use ($eventStoreFile, $allTasksProjectionFileName) {
    return new ProjectionBuildingEventStore(
        new Infrastructure\File\EventStore($eventStoreFile),
        new AllTasksProjector(new Infrastructure\File\AllTasksStorage($allTasksProjectionFileName))
    );
});

$injector->delegate(Domain\User\Query\GetUserById::class, function () use ($optionsFileName) {
    $optionsJson = file_get_contents($optionsFileName);
    $options = json_decode($optionsJson, true);
    $users = array_map(function(array $user) : Domain\User {
        return new Domain\User(
            Domain\User\Id::fromString($user['id']),
            $user['name']
        );
    }, $options['users']);

    return new Infrastructure\InMemory\GetUserById(...$users);
});

return $injector;
