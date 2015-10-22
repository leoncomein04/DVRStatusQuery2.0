<?php
require_once 'driverCLI.php';

$file = realpath('../app/DVRStatusQuery.xml');
$d = new driverCLI ($argv);
$d->driver_process ($file);
print 'done';