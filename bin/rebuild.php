<?php

/**
 * @var Auryn\Injector $injector
 */

use jjok\TodoTwo\Domain\EventStream;
use jjok\TodoTwo\Domain\Task\Projections\AllTasksProjector;

require_once __DIR__ . '/../vendor/autoload.php';

$injector = require __DIR__ . '/../app/dependencies.php';

$eventStream = $injector->make(EventStream::class);
$projector = $injector->make(AllTasksProjector::class);

$dataDir = dataDir($_ENV);
$allTasksProjectionFileName = $dataDir . 'tasks.json';

unlink($allTasksProjectionFileName);


$projector->apply($eventStream->all());
