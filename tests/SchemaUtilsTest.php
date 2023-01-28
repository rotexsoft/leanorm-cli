<?php
use \LeanOrmCli\SchemaUtils;

/**
 * Description of FileIoUtilsTest
 *
 * @author rotimi
 */
class SchemaUtilsTest extends \PHPUnit\Framework\TestCase {
    
    protected ?\PDO $pdo = null;
    
    // TO MAKE THIS CLASS WORK WITH OTHER PDO DRIVERS
    // STORE QUERIES IN ARRAYS KEYED BY DRIVER NAME
    // INJECT DSN, UNAME & PWD FOR NON-SQLITE DRIVERS
    // VIA ENVIRONMENT VARIABLES. GET DRIVER NAME 
    // AFTER CREATING THE PDO CONNECTION AND USE THAT 
    // TO GET APPROPRIATE QUERIES FROM THE ARRAY
    // RUN THE QUERIES IN SETUP. IN TEARDOWN
    // DROP TABLES AND VIEWS FOR NON SQLITE
    // DRIVERS.
    
    public function setUp(): void {
        
        parent::setUp();
        
        $this->pdo = new \PDO('sqlite::memory:');
        
        $this->pdo->exec("
            CREATE TABLE authors (
                author_id INTEGER PRIMARY KEY,
                name TEXT,
                m_timestamp TEXT NOT NULL,
                date_created TEXT NOT NULL
            )
        ");
        
        $this->pdo->exec("
            CREATE VIEW v_authors 
            AS 
            SELECT
                author_id,
                name,
                m_timestamp,
                date_created
            FROM
                authors
        ");
        
        $this->pdo->exec("
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
        ");
    }
    
    protected function tearDown(): void {
        
        parent::tearDown();
        
        unset($this->pdo);
        
        $this->pdo = null;
    }

    public function testThatFetchTableListFromDBWorksAsExpected() {
        
        $tablesAndViews = SchemaUtils::fetchTableListFromDB($this->pdo);
        
        self::assertContains('authors', $tablesAndViews);
        self::assertContains('v_authors', $tablesAndViews);
        self::assertContains('posts', $tablesAndViews);
    }

    public function testThatFetchTableColsFromDBWorksAsExpected() {
        
        $authorsColumns = SchemaUtils::fetchTableColsFromDB('authors', $this->pdo);
        $vauthorsColumns = SchemaUtils::fetchTableColsFromDB('v_authors', $this->pdo);
        $postsColumns = SchemaUtils::fetchTableColsFromDB('posts', $this->pdo);
        
        self::assertArrayHasKey('author_id', $authorsColumns);
        self::assertArrayHasKey('name', $authorsColumns);
        self::assertArrayHasKey('m_timestamp', $authorsColumns);
        self::assertArrayHasKey('date_created', $authorsColumns);
        
        self::assertArrayHasKey('author_id', $vauthorsColumns);
        self::assertArrayHasKey('name', $vauthorsColumns);
        self::assertArrayHasKey('m_timestamp', $vauthorsColumns);
        self::assertArrayHasKey('date_created', $vauthorsColumns);
        
        self::assertArrayHasKey('post_id', $postsColumns);
        self::assertArrayHasKey('author_id', $postsColumns);
        self::assertArrayHasKey('datetime', $postsColumns);
        self::assertArrayHasKey('title', $postsColumns);
        self::assertArrayHasKey('body', $postsColumns);
        self::assertArrayHasKey('m_timestamp', $postsColumns);
        self::assertArrayHasKey('date_created', $postsColumns);
    }
    
    public function testThatColumnExistsInDbTableWorksAsExpected() {
        
        self::assertTrue(SchemaUtils::columnExistsInDbTable('authors', 'name', $this->pdo));
        self::assertTrue(SchemaUtils::columnExistsInDbTable('v_authors', 'date_created', $this->pdo));
        self::assertTrue(SchemaUtils::columnExistsInDbTable('posts', 'body', $this->pdo));
        
        self::assertFalse(SchemaUtils::columnExistsInDbTable('authors777', 'name', $this->pdo));
        self::assertFalse(SchemaUtils::columnExistsInDbTable('v_authors777', 'date_created', $this->pdo));
        self::assertFalse(SchemaUtils::columnExistsInDbTable('posts777', 'body', $this->pdo));
    }
    
    public function testThatGetPdoDriverNameWorksAsExpected() {
        
        self::assertEquals(
            $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME), 
            SchemaUtils::getPdoDriverName($this->pdo)
        );
    }
    
    public function testThatGetCurrentConnectionInfoWorksAsExpected() {
        
        $info = SchemaUtils::getCurrentConnectionInfo($this->pdo);
        
        self::assertStringContainsString('Currently selected database schema:', $info);
        self::assertStringContainsString('database_server_info', $info);
        self::assertStringContainsString('driver_name', $info);
        self::assertStringContainsString('pdo_client_version', $info);
        self::assertStringContainsString('database_server_version', $info);
        self::assertStringContainsString('connection_status', $info);
        self::assertStringContainsString('connection_is_persistent', $info);

    }
}
