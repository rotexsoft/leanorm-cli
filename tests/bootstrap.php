<?php
error_reporting(E_ALL);

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor/autoload.php';
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'vendor/icanboogie/inflector/lib/helpers.php';

if( !file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'pdo.php') ) {
    
    copy(
        dirname(__DIR__).DIRECTORY_SEPARATOR. 'pdo-dist.php', 
        __DIR__ . DIRECTORY_SEPARATOR . 'pdo.php'    
    );
}
