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


use jjok\TodoTwo\Domain\ProjectionBuildingEventStore;
use jjok\TodoTwo\Domain\Task\Projections\AllTasksProjector;
use jjok\TodoTwo\Infrastructure\File\AllTasksStorage;
use jjok\TodoTwo\Infrastructure\File\EventStore;

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


$injector->defineParam('filename', $dataDir . 'tasks.json');

$injector->share(\jjok\TodoTwo\Domain\EventStream::class);
$injector->share(\jjok\TodoTwo\Domain\EventStore::class);

$injector->alias(\jjok\TodoTwo\Domain\EventStream::class, \jjok\TodoTwo\Infrastructure\File\EventStream::class);
$injector->alias(\jjok\TodoTwo\Domain\Task\Projections\AllTasksStorage::class, AllTasksStorage::class);

$injector->delegate(ProjectionBuildingEventStore::class, function(Auryn\Injector $injector) use ($eventStoreFile, $allTasksProjectionFileName) {
    return new ProjectionBuildingEventStore(
        new EventStore($eventStoreFile),
        new AllTasksProjector(new AllTasksStorage($allTasksProjectionFileName))
    );
});

return $injector;
