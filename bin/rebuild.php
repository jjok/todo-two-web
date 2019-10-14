<?php

/**
 * @var Auryn\Injector $injector
 */

use jjok\TodoTwo\Domain\EventStream;
use jjok\TodoTwo\Domain\Task\Projections\AllTasksProjector;

require_once __DIR__ . '/../vendor/autoload.php';

$injector = require __DIR__ . '/../app/dependencies.php';

$dataDir = dataDir($_ENV);
$allTasksProjectionFileName = $dataDir . 'tasks.json';

file_put_contents($allTasksProjectionFileName, '');

$eventStream = $injector->make(EventStream::class);
$projector = $injector->make(AllTasksProjector::class);


$projector->apply($eventStream->all());
