<?php
use Opis\Database\Database;
use \Opis\Database\Connection;
use \Opis\Database\Schema\CreateTable;

trait SchemaManagementTrait {

    public function doSetUp(\PDO $pdo): void {
        
        // Now using opis/database to create tables so that it generates the 
        // appropriate SQL create statements for the current pdo driver.
        // This makes the tests run with sqlite, mysql & postgres. 
        // Haven't tried sqlserver yet.
        $connection = Connection::fromPDO($pdo);
        $db = new Database($connection);
        $schema = $db->schema();
        
        $schema->create('authors', function(CreateTable $table) {
            
            //add table authors
            $table->integer('author_id')->autoincrement();
            $table->primary('author_id', 'author_id');
            
            $table->text('name');
            
            $table->text('m_timestamp')->notNull();
            $table->text('date_created')->notNull();
        });
        
        // Table creation method below is sqlite specific, 
        // won't work with mysql & the others
//        $this->pdo->exec("
//            CREATE TABLE authors (
//                author_id INTEGER PRIMARY KEY,
//                name TEXT,
//                m_timestamp TEXT NOT NULL,
//                date_created TEXT NOT NULL
//            )
//        ");
        
        // The veiw creation sql below works on sqlite, mysql & postgres
        // haven't tested with sqlserver though
        $this->pdo->exec("
            CREATE VIEW v_authors 
            AS 
            SELECT
                author_id,
                name,
                m_timestamp,
                date_created
            FROM authors
        ");
        
        // Table creation method below is sqlite specific, 
        // won't work with mysql & the others
//        $this->pdo->exec("
//            CREATE TABLE posts (
//              post_id INTEGER PRIMARY KEY,
//              author_id INTEGER NOT NULL,
//              datetime TEXT,
//              title TEXT,
//              body TEXT,
//              m_timestamp TEXT NOT NULL,
//              date_created TEXT NOT NULL,
//              FOREIGN KEY(author_id) REFERENCES authors(author_id)
//            )
//        ");
        
        $schema->create('posts', function(CreateTable $table) {
            
            //add table authors
            $table->integer('post_id')->autoincrement();
            $table->primary('post_id', 'post_id');
            
            $table->integer('author_id')->notNull();
            
            $table->text('datetime');
            $table->text('title');
            $table->text('body');
            
            $table->text('m_timestamp')->notNull();
            $table->text('date_created')->notNull();
            
            $table->foreign('author_id')
                  ->references('authors', 'author_id');
        });
    }
    
    protected function doTearDown(\PDO $pdo): void {
        
        $pdo->exec("DROP TABLE IF EXISTS posts;");
        $pdo->exec("DROP VIEW IF EXISTS v_authors;");
        $pdo->exec("DROP TABLE IF EXISTS authors;");
    }
}
