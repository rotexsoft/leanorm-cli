<?php
use \LeanOrmCli\SchemaUtils;
use \LeanOrmCli\FileIoUtils;

/**
 * Description of EndUserTest
 *
 * @author rotimi
 */
class EndUserTest extends \PHPUnit\Framework\TestCase {
    
    protected ?\PDO $pdo = null;
    
    use SchemaManagementTrait;
    
    public function setUp(): void {
        
        parent::setUp();
        
        $pdoArgs = include __DIR__ . DIRECTORY_SEPARATOR . 'pdo.php';
        $this->pdo = new \PDO(...$pdoArgs);
        
        $this->doSetUp($this->pdo);
    }
    
    protected function tearDown(): void {
        
        parent::tearDown();
        
        $this->doTearDown($this->pdo);
        
        unset($this->pdo);
        $this->pdo = null;
        
        $basePath = dirname(__FILE__).DIRECTORY_SEPARATOR
                    .'test-files'.DIRECTORY_SEPARATOR
                    .'actually-generated-classes'.DIRECTORY_SEPARATOR;
        
        if(
            FileIoUtils::isDir(
                $basePath .'Authors'
            )    
        ) {
            $this->rmdirRecursive($basePath .'Authors');
        }
        
        if(
            FileIoUtils::isDir(
                $basePath .'VAuthors'
            )    
        ) {
            $this->rmdirRecursive($basePath .'VAuthors');
        }
    }
    
    public function testThatScriptWorksAsExpected() {
        
        $ds = DIRECTORY_SEPARATOR;
        $basePath = dirname(__FILE__) . $ds .'test-files'. $ds;
        $basePathActual = $basePath . 'actually-generated-classes' . $ds;
        $basePathExpected = $basePath . 'expected-generated-classes' . $ds;
        
        $input =  [
            'pdo' => $this->pdo,                                                              

            'namespace' => 'App\\Models',                                       // Root Namespace classes will belong to. E.g. 'App\\DataSource'. Null means no namespace.
            'directory' => $basePath . 'actually-generated-classes',            // Absolute or relative path to where classes are to be written
            'custom_templates_directory' => $basePath . 'custom-templates',     // Absolute or relative path to a direcory containing template files named 
                                                                                // TypesModel.php.tpl, TypesCollection.php.tpl & TypeRecord.php.tpl
            'tables_to_skip' => ['posts'],                                      // List of tables to skip generating classes for
            'collection_class_to_extend' => '\\LeanOrm\\Model\\Collection',     // Class that all collection classes should extend
            'model_class_to_extend' => '\\LeanOrm\\CachingModel',               // Class that all model classes should extend
            'record_class_to_extend' => '\\LeanOrm\\Model\\ReadOnlyRecord',     // Class that all record classes should extend
            'created_timestamp_column_name' => 'date_created',                  // Name of a column in each table whose value will be updated with the time each row gets inserted
            'updated_timestamp_column_name' => 'm_timestamp',                   // Name of a column in each table whose value will be updated with the time each row gets updated

            'store_table_col_metadata_array_in_file' => true,
            
            'table_name_to_record_class_prefix_transformer' =>                  // A callback that accepts a db table name, modifies it & returns the modified value that will be used to substitute {{{RECORD_CLASS_NAME_PREFIX}}} in template files
                function(string $tableName): string {

                    $txtSeparatedWithSpaces = \ICanBoogie\StaticInflector::titleize($tableName, 'en');

                    if(str_contains($txtSeparatedWithSpaces, ' ')) {

                        $words = explode(' ', $txtSeparatedWithSpaces);
                        $singularizedWordsCamelCased = '';

                        foreach ($words as $word) {

                            $singularizedWordsCamelCased .= 
                                strlen($word) > 1
                                    ? \ICanBoogie\StaticInflector::singularize($word, 'en')
                                    : $word;
                        }

                    } else {

                        $singularizedWordsCamelCased = \ICanBoogie\StaticInflector::singularize($txtSeparatedWithSpaces, 'en');
                    }

                    return $singularizedWordsCamelCased;
                },

            'table_name_to_collection_and_model_class_prefix_transformer' =>    // A callback that accepts a db table name, modifies it & returns the modified value that will be used to substitute {{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}} in template files
                function(string $tableName): string {

                    $txtSeparatedWithSpaces = \ICanBoogie\StaticInflector::titleize($tableName, 'en');

                    if(str_contains($txtSeparatedWithSpaces, ' ')) {

                        $words = explode(' ', $txtSeparatedWithSpaces);
                        $pluralizedWordsCamelCased = '';

                        foreach ($words as $word) {

                            $pluralizedWordsCamelCased .= 
                                strlen($word) > 1
                                    ? \ICanBoogie\StaticInflector::pluralize($word, 'en')
                                    : $word;
                        }

                    } else {

                        $pluralizedWordsCamelCased = \ICanBoogie\StaticInflector::pluralize($txtSeparatedWithSpaces, 'en');
                    }

                    return $pluralizedWordsCamelCased;
                },
        ];

        $command = new \LeanOrmCli\OrmClassesGenerator($input);
        $command('authors'); // run command to generate for 1 table
        
        self::assertTrue(FileIoUtils::isDir($basePathActual));
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'Authors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ),
            "App\\Models\\Authors",
            'AuthorsModel',
            'AuthorRecord',
            'ReadOnlyRecord',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            true
        );
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'Authors'. $ds . 'AuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'Authors'. $ds . 'AuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' )
        );

        $this->tearDown();
        $this->setUp();
        
        $command2 = new \LeanOrmCli\OrmClassesGenerator($input);
        $command2(''); // run command to generate for all tables & views based on config
        
        
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'Authors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ),
            "App\\Models\\Authors",
            'AuthorsModel',
            'AuthorRecord',
            'ReadOnlyRecord',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            true
        );
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'Authors'. $ds . 'AuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'Authors'. $ds . 'AuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' )
        );
        
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'VAuthors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'VAuthors'. $ds . 'VAuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'VAuthors'. $ds . 'VAuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'VAuthors'. $ds . 'VAuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'VAuthors'. $ds . 'VAuthorRecord.php' ),
            "App\\Models\\VAuthors",
            'VAuthorsModel',
            'VAuthorRecord',
            'ReadOnlyRecord',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            true
        );
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'VAuthors'. $ds . 'VAuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'VAuthors'. $ds . 'VAuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'VAuthors'. $ds . 'VAuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'VAuthors'. $ds . 'VAuthorsModel.php' )
        );
        
        // confirm that the posts table was skipped and no classes were generated for it
        self::assertFalse(FileIoUtils::isDir($basePathActual. 'Post'));
        self::assertFalse(FileIoUtils::isDir($basePathActual. 'Posts'));
    }
    
    public function testThatScriptWithDefaultConfigWorksAsExpected() {
        
        $ds = DIRECTORY_SEPARATOR;
        $basePath = dirname(__FILE__) . $ds .'test-files'. $ds;
        $basePathActual = $basePath . 'actually-generated-classes' . $ds;
        $basePathExpected = $basePath . 'expected-generated-classes' . $ds;
        
        $input =  [
            'pdo' => $this->pdo,                                                              

            'directory' => $basePath . 'actually-generated-classes',            // Absolute or relative path to where classes are to be written
            'tables_to_skip' => ['posts'],                                      // List of tables to skip generating classes for
        ];

        $command = new \LeanOrmCli\OrmClassesGenerator($input);
        $command('authors'); // run command to generate for 1 table
        
        self::assertTrue(FileIoUtils::isDir($basePathActual));
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'Authors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ),
            "App\\Models\\Authors",
            'AuthorsModel',
            'AuthorRecord',
            'Record',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            false
        );
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' )
        );
        
        $this->tearDown();
        $this->setUp();
        
        $command2 = new \LeanOrmCli\OrmClassesGenerator($input);
        $command2(''); // run command to generate for all tables & views based on config
        
        
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'Authors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ),
            "App\\Models\\Authors",
            'AuthorsModel',
            'AuthorRecord',
            'Record',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            false
        );
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' )
        );
        
        ////////////////////////////////////////////////////////////////////////
        ////////////////////////////////////////////////////////////////////////
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'VAuthors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'VAuthors'. $ds . 'VAuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'VAuthors'. $ds . 'VAuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'VAuthors'. $ds . 'VAuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'VAuthors'. $ds . 'VAuthorRecord.php' ),
            "App\\Models\\VAuthors",
            'VAuthorsModel',
            'VAuthorRecord',
            'Record',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            false
        );
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'VAuthors'. $ds . 'VAuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'VAuthors'. $ds . 'VAuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'VAuthors'. $ds . 'VAuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'VAuthors'. $ds . 'VAuthorsModel.php' )
        );
        
        // confirm that the posts table was skipped and no classes were generated for it
        self::assertFalse(FileIoUtils::isDir($basePathActual. 'Post'));
        self::assertFalse(FileIoUtils::isDir($basePathActual. 'Posts'));
        
        
        // re run for single table, to make sure skipping of already generated files occurs
        $command3 = new \LeanOrmCli\OrmClassesGenerator($input);
        $command3('authors'); // run command to generate for 1 table
        
        self::assertTrue(FileIoUtils::isDir($basePathActual));
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'Authors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ),
            "App\\Models\\Authors",
            'AuthorsModel',
            'AuthorRecord',
            'Record',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            false
        );
        
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' )
        );
        
        // re run for skipped table, to make sure skipping of already generated files occurs
        $command3 = new \LeanOrmCli\OrmClassesGenerator($input);
        $command3('posts'); // run command to generate for 1 table
        
        self::assertTrue(FileIoUtils::isDir($basePathActual));
        self::assertTrue(FileIoUtils::isDir($basePathActual. 'Authors'));
        
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' ));
        self::assertTrue(FileIoUtils::isFile($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' ));
        
        $this->validateGeneratedRecordClass(
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorRecord.php' ),
            "App\\Models\\Authors",
            'AuthorsModel',
            'AuthorRecord',
            'Record',
            [
                'author_id',
                'name',
                'm_timestamp',
                'date_created',
            ],
            false
        );
        
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsCollection.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsCollection.php' )
        );
        self::assertEquals(
            FileIoUtils::get($basePathExpected. 'from-default-config' . $ds . 'Authors'. $ds . 'AuthorsModel.php' ),
            FileIoUtils::get($basePathActual. 'Authors'. $ds . 'AuthorsModel.php' )
        );
        
        // confirm that the posts table was skipped and no classes were generated for it
        self::assertFalse(FileIoUtils::isDir($basePathActual. 'Post'));
        self::assertFalse(FileIoUtils::isDir($basePathActual. 'Posts'));
    }
    
    // Validate generated record class this way because 
    // the various db engines have different column type definitions
    protected function validateGeneratedRecordClass(
        string $generatedClass,
        string $namespace,
        string $ModelClassName,
        string $recordClassName,
        string $extendedRecordClass='Record',
        array $propertyNames = [],
        bool $checkNamespaceLine = true
    ) {
        self::assertStringContainsString(
            "declare(strict_types=1);", 
            $generatedClass
        );
        
        if($checkNamespaceLine) {
            
            self::assertStringContainsString(
                "namespace {$namespace};", 
                $generatedClass
            );
                
        } else {
            
            self::assertStringNotContainsString(
                "namespace", 
                $generatedClass
            );
        }
        
        foreach($propertyNames as $propertyName) {
            
            self::assertStringContainsString(
                ' * @property mixed $' . $propertyName, 
                $generatedClass
            );
        }
        
        self::assertStringContainsString(
            " * @method {$ModelClassName} getModel()", 
            $generatedClass
        );
        
        self::assertStringContainsString(
            "class {$recordClassName} extends \\LeanOrm\\Model\\{$extendedRecordClass} {", 
            $generatedClass
        );
    }
    
    protected function rmdirRecursive($dir) {
        
        $iter = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($iter, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach($it as $file) {
            
            if ($file->isDir())  {
                
                rmdir($file->getPathname());
                
            } else {
                
                unlink($file->getPathname());
            }
        }
        
        rmdir($dir);
    }
}
