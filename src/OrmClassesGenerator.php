<?php
declare(strict_types=1);

namespace LeanOrmCli;

/**
 * OrmClassesGenerator is the main class that generates collection, model & record classes for any application using LeanOrm
 *
 * @author rotimi
 * @psalm-suppress UnusedClass
 */
class OrmClassesGenerator {

    /**
     * @var string[]
     */
    public const LIST_OF_SQLITE_TABLES_TO_SKIP = [
        'sqlite_master', 'sqlite_sequence', 'sqlite_stat1', 'sqlite_schema',
        'sqlite_temp_schema', 'sqlite_temp_master',
    ];

    /**
     * @var string
     */
    public const TEMPLATE_COLLECTION_FILE_NAME = 'TypesCollection.php.tpl';

    /**
     * @var string
     */
    public const TEMPLATE_FIELDS_METADATA_FILE_NAME = 'TypesFieldsTrait.tpl';

    /**
     * @var string
     */
    public const TEMPLATE_MODEL_FILE_NAME = 'TypesModel.php.tpl';

    /**
     * @var string
     */
    public const TEMPLATE_RECORD_FILE_NAME = 'TypeRecord.php.tpl';

    protected array $defaultConfig = [];

    protected string $defaultTemplatesDirectory = '';

    protected ?string $customTemplatesDirectory = null;

    /**
     * @var string[]
     */
    protected array $tableAndViewNames = [];

    protected \PDO $pdo;

    protected string $destinationDirectory = '';

    protected string $loadedCollectionTemplateFile = '';

    protected string $loadedFieldsMetadataTemplateFile = '';

    protected string $loadedModelTemplateFile = '';

    protected string $loadedRecordTemplateFile = '';

    protected array $filesToWrite = [];

    public function __construct(protected array $config) {

        $ds = DIRECTORY_SEPARATOR;
        $this->defaultTemplatesDirectory = realpath(__DIR__ . "{$ds}..{$ds}templates{$ds}");

        /**
         * @psalm-suppress MixedAssignment
         * @psalm-suppress UnresolvableInclude
         */
        $this->defaultConfig = require __DIR__ . "{$ds}..{$ds}sample-config.php";

        if(!array_key_exists('pdo', $this->config)) {

            throw new \Exception('`pdo` entry is missing in config!');

        } elseif (!is_array($this->config['pdo']) && !($this->config['pdo'] instanceof \PDO)) {

            throw new \Exception('`pdo` entry in config is not an array & is also not a PDO instance!');
        }

        /**
         * @psalm-suppress MixedArgument
         */
        $this->pdo = ($this->config['pdo'] instanceof \PDO)
                        ? $this->config['pdo']
                        : new \PDO(...$this->config['pdo']);

        /**
         * Fill config with defaults
         * 
         * @psalm-suppress MixedAssignment
         */
        foreach ($this->defaultConfig as $key=>$val) {

            /**
             * @psalm-suppress MixedArgument
             */
            if(!array_key_exists($key, $this->config)) {

                /**
                 * @psalm-suppress MixedArrayOffset
                 */
                $this->config[$key] = $val;
            }
        }
        
        ////////////////////////////////////////////////////////////////////////
        // VALIDATE THE REMAINING ENTRIES IN THE CONFIG
        ////////////////////////////////////////////////////////////////////////
        
        if(!is_string($this->config['namespace']) && $this->config['namespace']!== null) {

            throw new \Exception('`namespace` entry in config is not a string!');
        }

        if(!is_string($this->config['directory'])) {

            throw new \Exception('`directory` entry in config is not a string!');
        }
        
        if(!FileIoUtils::isDir($this->config['directory'])) {

            throw new \Exception('`directory` entry in config is not a valid directory!');
        }

        $this->destinationDirectory  = $this->config['directory'];

        if(
            OtherUtils::isNonEmptyString($this->config['custom_templates_directory'])
        ) {
            /**
             * @psalm-suppress MixedArgument
             * @psalm-suppress PossiblyInvalidCast
             * @psalm-suppress PossiblyInvalidArgument
             */
            if(!FileIoUtils::isDir($this->config['custom_templates_directory'])) {

                throw new \Exception('`custom_templates_directory` entry in config is not a valid directory!');
            } 

            /**
             * @psalm-suppress MixedAssignment
             */
            $this->customTemplatesDirectory = $this->config['custom_templates_directory'];
            
        } elseif (
            !is_string($this->config['custom_templates_directory'])    
            && $this->config['custom_templates_directory'] !== null
        ) {
            throw new \Exception('`custom_templates_directory` entry in config is not a string!');
        }
        
        if(!is_array($this->config['tables_to_skip'])) {

            throw new \Exception('`tables_to_skip` entry in config is not an array of strings (names of tables & views to skip)!');
        }
        
        if(!is_string($this->config['collection_class_to_extend'])) {

            throw new \Exception('`collection_class_to_extend` entry in config is not a string!');
        }
        
        if(!is_string($this->config['model_class_to_extend'])) {

            throw new \Exception('`model_class_to_extend` entry in config is not a string!');
        }
        
        if(!is_string($this->config['record_class_to_extend'])) {

            throw new \Exception('`record_class_to_extend` entry in config is not a string!');
        }
        
        if(!is_string($this->config['created_timestamp_column_name']) && $this->config['created_timestamp_column_name']!== null) {

            throw new \Exception('`created_timestamp_column_name` entry in config is not a string!');
        }
        
        if(!is_string($this->config['updated_timestamp_column_name']) && $this->config['updated_timestamp_column_name']!== null) {

            throw new \Exception('`updated_timestamp_column_name` entry in config is not a string!');
        }
        
        if(!is_callable($this->config['table_name_to_record_class_prefix_transformer'])) {

            throw new \Exception('`table_name_to_record_class_prefix_transformer` entry in config is not a callable!');
        }
        
        if(!is_callable($this->config['table_name_to_collection_and_model_class_prefix_transformer'])) {

            throw new \Exception('`table_name_to_collection_and_model_class_prefix_transformer` entry in config is not a callable!');
        }
    }

    /**
     * @param string $tableOrViewName name of the table or view you want to generate files for. 
     *                                If empty string, it will generate for all tables & views.
     */
    public function __invoke(string $tableOrViewName='') : ?int {

        try {
            return $this->loadTableAndViewNames()
                ?? $this->loadTemplateFiles()
                ?? $this->generateClassFiles($tableOrViewName)
                ?? $this->writeGeneratedClassFilesToDestinationDirectory();

        } catch (\Exception $e) {

            echo OtherUtils::getThrowableAsStr($e);
            return 1;
        }
    }

    protected function loadTemplateFiles(): ?int {

        if(
            $this->customTemplatesDirectory !== null
            && FileIoUtils::isDir($this->customTemplatesDirectory)
        ) {
            /////////////////////////////////////////////////////////
            // Try loading template files from custom location first
            /////////////////////////////////////////////////////////

            echo "Trying to load template files from custom location `{$this->customTemplatesDirectory}` ....". PHP_EOL;

            $templateCollectionFilePath = FileIoUtils::concatDirAndFileName(
                $this->customTemplatesDirectory, 
                static::TEMPLATE_COLLECTION_FILE_NAME
            );
            $templateFieldsMetadataFilePath = FileIoUtils::concatDirAndFileName(
                $this->customTemplatesDirectory, 
                static::TEMPLATE_FIELDS_METADATA_FILE_NAME
            );
            $templateModelFilePath = FileIoUtils::concatDirAndFileName(
                $this->customTemplatesDirectory, 
                static::TEMPLATE_MODEL_FILE_NAME
            );
            $templateRecordFilePath = FileIoUtils::concatDirAndFileName(
                $this->customTemplatesDirectory, 
                static::TEMPLATE_RECORD_FILE_NAME
            );

            if(FileIoUtils::isFile($templateCollectionFilePath)) {

                $this->loadedCollectionTemplateFile = 
                    FileIoUtils::get($templateCollectionFilePath);
                echo "Successfully loaded template file `{$templateCollectionFilePath}` from custom location". PHP_EOL;
            }

            if(FileIoUtils::isFile($templateFieldsMetadataFilePath)) {

                $this->loadedFieldsMetadataTemplateFile = 
                    FileIoUtils::get($templateFieldsMetadataFilePath);
                echo "Successfully loaded template file `{$templateFieldsMetadataFilePath}` from custom location". PHP_EOL;
            }

            if(FileIoUtils::isFile($templateModelFilePath)) {

                $this->loadedModelTemplateFile = 
                    FileIoUtils::get($templateModelFilePath);
                echo "Successfully loaded template file `{$templateModelFilePath}` from custom location". PHP_EOL;
            }

            if(FileIoUtils::isFile($templateRecordFilePath)) {

                $this->loadedRecordTemplateFile = 
                    FileIoUtils::get($templateRecordFilePath);
                echo "Successfully loaded template file `{$templateRecordFilePath}` from custom location". PHP_EOL;
            }
        }

        ////////////////////////////////////////////////////////////////
        // Next, try loading default template files for those
        // templates that could not be loaded from the custom location.
        ////////////////////////////////////////////////////////////////
        echo "Trying to load template files from default location `{$this->defaultTemplatesDirectory}` ....". PHP_EOL;

        $templateCollectionFilePath = FileIoUtils::concatDirAndFileName(
            $this->defaultTemplatesDirectory, 
            static::TEMPLATE_COLLECTION_FILE_NAME
        );
        $templateFieldsMetadataFilePath = FileIoUtils::concatDirAndFileName(
            $this->defaultTemplatesDirectory, 
            static::TEMPLATE_FIELDS_METADATA_FILE_NAME
        );
        $templateModelFilePath = FileIoUtils::concatDirAndFileName(
            $this->defaultTemplatesDirectory, 
            static::TEMPLATE_MODEL_FILE_NAME
        );
        $templateRecordFilePath = FileIoUtils::concatDirAndFileName(
            $this->defaultTemplatesDirectory, 
            static::TEMPLATE_RECORD_FILE_NAME
        );

        if($this->loadedCollectionTemplateFile === '') {

            $this->loadedCollectionTemplateFile = 
                    FileIoUtils::get($templateCollectionFilePath);
            echo "Successfully loaded template file `{$templateCollectionFilePath}` from default location". PHP_EOL;
        }

        if($this->loadedFieldsMetadataTemplateFile === '') {

            $this->loadedFieldsMetadataTemplateFile = 
                    FileIoUtils::get($templateFieldsMetadataFilePath);
            echo "Successfully loaded template file `{$templateFieldsMetadataFilePath}` from default location". PHP_EOL;
        }

        if($this->loadedModelTemplateFile === '') {

            $this->loadedModelTemplateFile = 
                    FileIoUtils::get($templateModelFilePath);
            echo "Successfully loaded template file `{$templateModelFilePath}` from default location". PHP_EOL;
        }

        if($this->loadedRecordTemplateFile === '') {

            $this->loadedRecordTemplateFile = 
                    FileIoUtils::get($templateRecordFilePath);
            echo "Successfully loaded template file `{$templateRecordFilePath}` from default location". PHP_EOL;
        }

        echo PHP_EOL . "Done loading template files!". PHP_EOL. PHP_EOL;

        return null;
    }

    /**
     * @param string $tableOrViewName name of the table or view you want to generate files for. 
     *                                If empty string, it will generate for all tables & views.
     */
    protected function generateClassFiles(string $tableOrViewName=''): ?int {

        echo "Generating class files ....". PHP_EOL;

        foreach ($this->tableAndViewNames as $tableName) {
            
            if($tableOrViewName !== '' && $tableName !== $tableOrViewName) {

                continue; // check the next table name (if any) on the next iteration
            }

            echo "\tGenerating class files for table `{$tableName}` ....". PHP_EOL;

            /**
             * @psalm-suppress MixedAssignment
             * @psalm-suppress MixedFunctionCall
             */
            $collectionOrModelNamePrefix = 
                $this->config['table_name_to_collection_and_model_class_prefix_transformer']($tableName);

            /**
             * @psalm-suppress MixedAssignment
             * @psalm-suppress MixedFunctionCall
             */
            $recordNamePrefix =
                $this->config['table_name_to_record_class_prefix_transformer']($tableName);

            /**
             * @psalm-suppress MixedOperand
             */
            $collectionClassName = $collectionOrModelNamePrefix. 'Collection.php';
            /**
             * @psalm-suppress MixedOperand
             */
            $modelClassName = $collectionOrModelNamePrefix. 'Model.php';
            /**
             * @psalm-suppress MixedOperand
             */
            $fieldsFileName = $collectionOrModelNamePrefix. 'FieldsMetadata.php';
            /**
             * @psalm-suppress MixedOperand
             */
            $recordClassName = $recordNamePrefix. 'Record.php';

            echo "\t\tCollection class file name: `{$collectionClassName}`". PHP_EOL;
            echo "\t\tModel class file name: `{$modelClassName}`". PHP_EOL;
            echo "\t\tFields Metadata file name: `{$fieldsFileName}`". PHP_EOL;
            echo "\t\tRecord class file name: `{$recordClassName}`". PHP_EOL;

            /**
             * @psalm-suppress MixedArgument
             */
            $destinationDirectory = 
                FileIoUtils::concatDirAndFileName($this->destinationDirectory, $collectionOrModelNamePrefix);

            echo "\tClass files for table `{$tableName}` will be written to `{$destinationDirectory}`". PHP_EOL;

            $this->filesToWrite[$destinationDirectory] = [];

            /**
             * @psalm-suppress MixedArgument
             */
            $this->filesToWrite[$destinationDirectory][$collectionClassName]
                = $this->generateCollectionClassFile($tableName, $collectionOrModelNamePrefix, $recordNamePrefix);

            $this->filesToWrite[$destinationDirectory][$modelClassName]
                = $this->generateModelClassFile($tableName, $collectionOrModelNamePrefix, $recordNamePrefix);

            $this->filesToWrite[$destinationDirectory][$recordClassName]
                = $this->generateRecordClassFile($tableName, $collectionOrModelNamePrefix, $recordNamePrefix);
            
            if($this->config['store_table_col_metadata_array_in_file']) {
                
                $this->filesToWrite[$destinationDirectory][$fieldsFileName]
                    = $this->generateFieldsMetadataFile($tableName, $collectionOrModelNamePrefix, $recordNamePrefix);
            }

            echo PHP_EOL;
        }

        echo "Finished generating class files!". PHP_EOL. PHP_EOL;

        return null;
    }

    protected function generateCollectionClassFile(string $tableName, string $collectionOrModelNamePrefix, string $recordNamePrefix): string {

        $translations = [
            '{{{NAME_SPACE}}}'                              => ($this->config['namespace'] === null) ? '' : "namespace {$this->config['namespace']}\\{$collectionOrModelNamePrefix};",
            '{{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}'   => $collectionOrModelNamePrefix,
            '{{{COLLECTION_EXTENDED}}}'                     => $this->config['collection_class_to_extend'],
            '{{{MODEL_EXTENDED}}}'                          => $this->config['model_class_to_extend'],
            '{{{RECORD_CLASS_NAME_PREFIX}}}'                => $recordNamePrefix,
            '{{{TABLE_NAME}}}'                              => $tableName,
        ];

        return strtr($this->loadedCollectionTemplateFile, $translations);
    }

    /**
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function generateFieldsMetadataFile(string $tableName, string $collectionOrModelNamePrefix, string $recordNamePrefix): string {

        $colDefs = SchemaUtils::fetchTableColsFromDB($tableName, $this->pdo);
        $metadataArray = [];
        
        foreach($colDefs as $col) {
            
            $metadataArray[$col->name] = [];
            $metadataArray[$col->name]['name'] = $col->name;
            $metadataArray[$col->name]['type'] = $col->type;
            $metadataArray[$col->name]['size'] = $col->size;
            $metadataArray[$col->name]['scale'] = $col->scale;
            $metadataArray[$col->name]['notnull'] = $col->notnull;
            $metadataArray[$col->name]['default'] = $col->default;
            $metadataArray[$col->name]['autoinc'] = $col->autoinc;
            $metadataArray[$col->name]['primary'] = $col->primary;
        }
        
        $translations = [
            '{{{METADATA_ARRAY}}}' => var_export($metadataArray, true),
        ];

        return strtr($this->loadedFieldsMetadataTemplateFile, $translations);
    }

    /**
     * @psalm-suppress MixedArgument
     */
    protected function generateModelClassFile(string $tableName, string $collectionOrModelNamePrefix, string $recordNamePrefix): string {

        $colDefs = SchemaUtils::fetchTableColsFromDB($tableName, $this->pdo);

        $createdColExists = 
            OtherUtils::isNonEmptyString($this->config['created_timestamp_column_name'])
                && SchemaUtils::columnExistsInDbTable($tableName, $this->config['created_timestamp_column_name'], $this->pdo);

        $updatedColExists = 
            OtherUtils::isNonEmptyString($this->config['updated_timestamp_column_name'])
                && SchemaUtils::columnExistsInDbTable($tableName, $this->config['updated_timestamp_column_name'], $this->pdo);

        $primaryColName = '';

        foreach ($colDefs as $col) {

            if( $col->primary ) {

                //this is a primary column
                $primaryColName = $col->name;
            }
        }

        $translations = [
            '{{{NAME_SPACE}}}'                              => ($this->config['namespace'] === null) ? '' : "namespace {$this->config['namespace']}\\{$collectionOrModelNamePrefix};",
            '{{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}'   => $collectionOrModelNamePrefix,
            '{{{COLLECTION_EXTENDED}}}'                     => $this->config['collection_class_to_extend'],
            '{{{MODEL_EXTENDED}}}'                          => $this->config['model_class_to_extend'],
            '{{{RECORD_EXTENDED}}}'                         => $this->config['record_class_to_extend'],
            '{{{RECORD_CLASS_NAME_PREFIX}}}'                => $recordNamePrefix,
            '{{{CREATED_TIMESTAMP_COLUMN_NAME}}}'           => ($this->config['created_timestamp_column_name'] === null || !$createdColExists) ? 'null' : "'{$this->config['created_timestamp_column_name']}'",
            '{{{UPDATED_TIMESTAMP_COLUMN_NAME}}}'           => ($this->config['updated_timestamp_column_name'] === null || !$updatedColExists) ? 'null' : "'{$this->config['updated_timestamp_column_name']}'",
            '{{{PRIMARY_COL_NAME}}}'                        => $primaryColName,
            '{{{TABLE_NAME}}}'                              => $tableName,
            '{{{INCLUDE_TABLE_COL_METADATA}}}'                               => ($this->config['store_table_col_metadata_array_in_file']) 
                                                                ? "\$this->table_cols = include(__DIR__ . DIRECTORY_SEPARATOR . '{$collectionOrModelNamePrefix}FieldsMetadata.php');" 
                                                                : '',
        ];
                                                                
        return strtr($this->loadedModelTemplateFile, $translations);
    }

    protected function generateRecordClassFile(string $tableName, string $collectionOrModelNamePrefix, string $recordNamePrefix): string {

        $phpDocDbColNamesAsPhpClassProperties = $this->generateColNamesAsPhpDocClassProperties($tableName);

        $translations = [
            '{{{NAME_SPACE}}}'                              => ($this->config['namespace'] === null) ? '' : "namespace {$this->config['namespace']}\\{$collectionOrModelNamePrefix};",
            '{{{RECORD_CLASS_NAME_PREFIX}}}'                => $recordNamePrefix,
            '{{{COLLECTION_EXTENDED}}}'                     => $this->config['collection_class_to_extend'],
            '{{{MODEL_EXTENDED}}}'                          => $this->config['model_class_to_extend'],
            '{{{RECORD_EXTENDED}}}'                         => $this->config['record_class_to_extend'],
            '{{{DB_COLS_AS_PHP_CLASS_PROPERTIES}}}'         => $phpDocDbColNamesAsPhpClassProperties,
            '{{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}'   => $collectionOrModelNamePrefix,
            '{{{TABLE_NAME}}}'                              => $tableName,
        ];

        return strtr($this->loadedRecordTemplateFile, $translations);
    }

    /**
     * @psalm-suppress RedundantCastGivenDocblockType
     */
    protected function generateColNamesAsPhpDocClassProperties(string $tableName): string {

        $colDefs = SchemaUtils::fetchTableColsFromDB($tableName, $this->pdo);
        $props = '';

        foreach ($colDefs as $col) {

            $coltype = $col->type;

            $unsigned = '';
            if (str_ends_with(strtoupper((string) $coltype), ' UNSIGNED')) {

                $unsigned = substr((string) $coltype, -9);
                $coltype = substr((string) $coltype, 0, -9);
            }

            $props .= " * @property mixed \${$col->name} {$coltype}";
            
            if ($col->size !== null) {
                $props .= "({$col->size}";

                if ($col->scale !== null) {

                    $props .= ", {$col->scale}";
                }

                $props .= ')';
            }

            $props .= $unsigned;

            if ($col->notnull === true) {
                $props .= ' NOT NULL';
            }

            $props .= PHP_EOL; 
        }

        return rtrim($props);
    }

    protected function writeGeneratedClassFilesToDestinationDirectory(): ?int {

        if($this->filesToWrite !== []) {
            
            echo "Creating generated collection, model & record class files ....". PHP_EOL;

            /**
             * @psalm-suppress MixedAssignment
             */
            foreach ($this->filesToWrite as $modelDirectory => $modelFilesInfo) {

                /**
                 * @psalm-suppress MixedArgumentTypeCoercion
                 */
                $this->mkdir($modelDirectory);

                /**
                 * @psalm-suppress MixedAssignment
                 */
                foreach ($modelFilesInfo as $fileName => $fileContents) {

                    /**
                     * @psalm-suppress MixedArgument
                     * @psalm-suppress MixedArgumentTypeCoercion
                     */
                    $destinationFile = FileIoUtils::concatDirAndFileName($modelDirectory, $fileName);

                    if(FileIoUtils::isFile($destinationFile) && !str_contains($destinationFile, 'FieldsMetadata')) {

                        echo "Skipping creation of `{$destinationFile}`, it already exists!" . PHP_EOL;
                        continue;
                    }

                    echo ((FileIoUtils::isFile($destinationFile))? "Updating " : "Creating " )
                         . "`{$destinationFile}`!" . PHP_EOL;

                    /**
                     * @psalm-suppress MixedArgument
                     */
                    FileIoUtils::put($destinationFile, $fileContents);
                }

                echo PHP_EOL;
            }

            $fullDestination = realpath($this->destinationDirectory);
            echo PHP_EOL . "Done creating all collection, model & record class files. " . PHP_EOL
                . "They are all located in `{$fullDestination}`." . PHP_EOL
                . "Goodbye!" . PHP_EOL . PHP_EOL;
        } else {
            
            echo "No collection, model or record class files were created based on the config values you specified.". PHP_EOL
               . "Goodbye!" . PHP_EOL . PHP_EOL;
        } // if($this->filesToWrite !== []) ...else
        
        return null;
    }

    protected function mkdir(string $dir) : ?int {

        if (FileIoUtils::isDir($dir)) {

            echo "Skipped: mkdir {$dir}" . PHP_EOL;
            return null;
        }

        try {

            FileIoUtils::mkdir($dir, 0755, true);

        } catch (\Exception $e) {

            echo "Failure: mkdir {$dir}" . PHP_EOL;
            echo OtherUtils::getThrowableAsStr($e) . PHP_EOL;

            return 1;
        }

        echo "Success: mkdir {$dir}" . PHP_EOL;
        return null;
    }

    protected function loadTableAndViewNames(): ?int {

        echo 'Getting a list of database tables to generate collection, model & record classes for .....' . PHP_EOL . PHP_EOL;
        echo 'Database Info:' . PHP_EOL;
        echo SchemaUtils::getCurrentConnectionInfo($this->pdo) . PHP_EOL;

        $tableNames = SchemaUtils::fetchTableListFromDB($this->pdo);

        sort($tableNames);

        /**
         * @psalm-suppress MixedAssignment
         */
        foreach($tableNames as $tableName) {

            /**
             * @psalm-suppress MixedArgument
             */
            if(
                (
                    strtolower(SchemaUtils::getPdoDriverName($this->pdo)) === 'sqlite'
                    && 
                    ( 
                        in_array(strtolower((string) $tableName), static::LIST_OF_SQLITE_TABLES_TO_SKIP)
                        || str_contains(strtolower((string) $tableName), 'sqlite')
                    )
                ) // sqlite filteration
                || in_array($tableName, $this->config['tables_to_skip']) // user specified filteration
            ) {
                echo "Skipping table `{$tableName}`" . PHP_EOL;
                continue; // don't add this table
            }

            echo "Adding table `{$tableName}`" . PHP_EOL;
            /**
             * @psalm-suppress MixedPropertyTypeCoercion
             */
            $this->tableAndViewNames[] = $tableName;

        } // foreach($tableNames as $tableName)

        echo PHP_EOL .'Finished getting a list of database tables to generate collection, model & record classes for!' . PHP_EOL . PHP_EOL;

        return null;
    }
}
