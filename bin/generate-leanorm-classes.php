#!/usr/bin/env php
<?php
error_reporting(E_ALL);

$autoload = false;
$inflectorHelper = false;
$files = [
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php'
];
$inflectorHelpers = [
    __DIR__ . '/../../../icanboogie/inflector/lib/helpers.php',
    __DIR__ . '/../../vendor/icanboogie/inflector/lib/helpers.php',
    __DIR__ . '/../vendor/icanboogie/inflector/lib/helpers.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $autoload = $file;
        break;
    }
}

foreach ($inflectorHelpers as $file) {
    if (file_exists($file)) {
        $inflectorHelper = $file;
        break;
    }
}

if (! $autoload) {
    echo "Please install and update Composer before continuing." . PHP_EOL;
    exit(1);
}

if (! $inflectorHelper) {
    echo "Error: Could not load inflector helper." . PHP_EOL;
    exit(1);
}

require_once $autoload;
require_once $inflectorHelper;

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

$tableOrViewNameIfAny='';

if (isset($_SERVER['argv'][2]) && is_string($_SERVER['argv'][2]) && $_SERVER['argv'][2] !== '') {
    $tableOrViewNameIfAny = $_SERVER['argv'][2];
}

try {
    
    $command = new \LeanOrmCli\OrmClassesGenerator($input);
    echo "************************************************" . PHP_EOL;
    echo "Successfully loaded LeanOrm Cli .............."    . PHP_EOL;
    echo "************************************************" . PHP_EOL . PHP_EOL;
    
    if($tableOrViewNameIfAny === '') {
        
        echo "Classes will be generated for all tables and views (apart from tables & views specified to be skipped in the config file)." . PHP_EOL . PHP_EOL;
        
    } else {
        
        echo "Classes will be generated for only `{$tableOrViewNameIfAny}` (as long as it's not specified to be skipped in the config file)." . PHP_EOL . PHP_EOL;
    }
    
    $code = $command($tableOrViewNameIfAny);
    exit($code);
    
} catch (\Exception $e) {

    echo \LeanOrmCli\OtherUtils::getThrowableAsStr($e);
    exit (1);
}
