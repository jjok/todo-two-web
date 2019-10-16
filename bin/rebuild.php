<?php

/**
 * @var Auryn\Injector $injector
 */

use jjok\TodoTwo\Domain\EventStream;
use jjok\TodoTwo\Domain\Task\Projections\AllTasksProjector;

require_once __DIR__ . '/../vendor/autoload.php';

$injector = require __DIR__ . '/../app/dependencies.php';

try {
    $eventStream = $injector->make(EventStream::class);
    $projector = $injector->make(AllTasksProjector::class);

    $projector->rebuild($eventStream);

    fwrite(STDOUT, 'Tasks projection rebuilt.' .PHP_EOL);
}
catch (Throwable $e) {
    fwrite(STDERR, $e);
    exit(1);
}
