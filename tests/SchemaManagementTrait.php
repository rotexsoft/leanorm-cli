<?php

trait SchemaManagementTrait {

    public function doSetUp(\PDO $pdo): void {
        
        $driver_name = strtolower(\LeanOrmCli\SchemaUtils::getPdoDriverName($pdo));
        
        $create_queries = [
            'sqlite' => [
                "
                    CREATE TABLE authors (
                        author_id INTEGER PRIMARY KEY,
                        name TEXT,
                        m_timestamp TEXT NOT NULL,
                        date_created TEXT NOT NULL
                    )
                ",
                "            
                    CREATE VIEW v_authors 
                    AS 
                    SELECT
                        author_id,
                        name,
                        m_timestamp,
                        date_created
                    FROM
                        authors
                ",
                "            
                    CREATE TABLE posts (
                      post_id INTEGER PRIMARY KEY,
                      author_id INTEGER NOT NULL,
                      datetime TEXT,
                      title TEXT,
                      body TEXT,
                      m_timestamp TEXT NOT NULL,
                      date_created TEXT NOT NULL,
                      FOREIGN KEY(author_id) REFERENCES authors(author_id)
                    )
                ",
            ],
            'mysql' => [
                "
                    CREATE TABLE `authors` (
                      `author_id` int unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) DEFAULT NULL,
                      `m_timestamp` datetime NOT NULL,
                      `date_created` datetime NOT NULL,
                      PRIMARY KEY (`author_id`)
                    )
                ",
                "            
                    CREATE VIEW `v_authors` AS 
                    SELECT
                      `authors`.`author_id`    AS `author_id`,
                      `authors`.`name`         AS `name`,
                      `authors`.`m_timestamp`  AS `m_timestamp`,
                      `authors`.`date_created` AS `date_created`
                    FROM `authors`
                ",
                "            
                    CREATE TABLE `posts` (
                      `post_id` int unsigned NOT NULL AUTO_INCREMENT,
                      `author_id` int unsigned NOT NULL,
                      `datetime` datetime DEFAULT NULL,
                      `title` varchar(255) DEFAULT NULL,
                      `body` text,
                      `m_timestamp` datetime NOT NULL,
                      `date_created` datetime NOT NULL,
                      PRIMARY KEY (`post_id`),
                      KEY `fk_posts_belong_to_an_author` (`author_id`),
                      CONSTRAINT `fk_posts_belong_to_an_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`) ON DELETE CASCADE ON UPDATE CASCADE
                    )
                ",
            ],
            'pgsql' => [
                "
                    CREATE TABLE authors (
                      author_id SERIAL PRIMARY KEY,
                      name varchar(255) DEFAULT NULL,
                      m_timestamp TIMESTAMP NOT NULL,
                      date_created TIMESTAMP NOT NULL
                    )
                ",
                "            
                    CREATE VIEW v_authors AS 
                    SELECT
                      authors.author_id    AS author_id,
                      authors.name         AS name,
                      authors.m_timestamp  AS m_timestamp,
                      authors.date_created AS date_created
                    FROM authors
                ",
                "            
                    CREATE TABLE posts (
                      post_id SERIAL PRIMARY KEY,
                      author_id int NOT NULL,
                      datetime TIMESTAMP DEFAULT NULL,
                      title varchar(255) DEFAULT NULL,
                      body text,
                      m_timestamp TIMESTAMP NOT NULL,
                      date_created TIMESTAMP NOT NULL
                    )
                ",  
            ],
        ];
        
        foreach ($create_queries[$driver_name] as $query) {
            
            $pdo->exec($query);
        }
    }
    
    protected function doTearDown(\PDO $pdo): void {
        
        $pdo->exec("DROP TABLE IF EXISTS posts;");
        $pdo->exec("DROP VIEW IF EXISTS v_authors;");
        $pdo->exec("DROP TABLE IF EXISTS authors;");
    }
}
