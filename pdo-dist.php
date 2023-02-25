<?php
// NOTE: Because of the way the test-suite is designed, in-memory sqlite does not work. 
// This means that you should not use the DSN below for sqlite:
//      sqlite::memory: 
// The sqlite db must be stored in a file. A default file is already configured below.

// Args for the PDO constructor
//public PDO::__construct(
//    string $dsn,
//    ?string $username = null,
//    ?string $password = null,
//    ?array $options = null
//)

return [
    'sqlite:'.  __DIR__ . DIRECTORY_SEPARATOR . 'sqlite' . DIRECTORY_SEPARATOR . 'blog.sqlite',  // string $dsn
    null,               // ?string $username
    null,               // ?string $password
    [],                 // options
];
