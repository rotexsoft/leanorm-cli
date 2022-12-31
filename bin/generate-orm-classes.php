#!/usr/bin/env php
<?php
error_reporting(E_ALL);

$autoload = false;
$files = [
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $autoload = $file;
        break;
    }
}

if (! $autoload) {
    echo "Please install and update Composer before continuing." . PHP_EOL;
    exit(1);
}

require $autoload;

if (! isset($_SERVER['argv'][1])) {
    echo "Please specify the path to a config file." . PHP_EOL;
    exit(1);
}

$configFile = $_SERVER['argv'][1];
if (! file_exists($configFile) && ! is_readable($configFile)) {
    echo "Config file missing or not readable: {$configFile}" . PHP_EOL;
    exit(1);
}

$input = require $configFile;
if (! is_array($input)) {
    echo "Config file '$configFile' does not return a PHP array." . PHP_EOL;
    exit(1);
}

try {
    
    $command = new \LeanOrmCli\OrmClassesGenerator($input);
var_dump($command);
    $code = $command();
    exit($code);
    
} catch (\Exception $e) {

    echo \LeanOrmCli\OtherUtils::getThrowableAsStr($e);
    exit (1);
}
