<?php
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
