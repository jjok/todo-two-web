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

    //TODO This better. Use projection version or something.
    $count = 0;
    foreach ($eventStream->all() as $event) {
        $count++;
    }

    fwrite(STDOUT, sprintf('Tasks projection rebuilt. %u events applied.' .PHP_EOL, $count));
}
catch (Throwable $e) {
    fwrite(STDERR, $e);
    exit(1);
}
