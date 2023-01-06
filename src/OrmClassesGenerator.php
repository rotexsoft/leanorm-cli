<?php
declare(strict_types=1);

namespace LeanOrmCli;

/**
 * OrmClassesGenerator is the main class that generates collection, model & record classes for any application using LeanOrm
 *
 * @author rotimi
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
    public const TEMPLATE_MODEL_FILE_NAME = 'TypesModel.php.tpl';

    /**
     * @var string
     */
    public const TEMPLATE_RECORD_FILE_NAME = 'TypeRecord.php.tpl';

    /**
     * @var string
     */
    public const DEFAULT_COLLECTION_CLASS_TO_EXTEND = '\\LeanOrm\\Model\\Collection';

    /**
     * @var string
     */
    public const DEFAULT_MODEL_CLASS_TO_EXTEND = '\\LeanOrm\\Model';

    /**
     * @var string
     */
    public const DEFAULT_RECORD_CLASS_TO_EXTEND = '\\LeanOrm\\Model\\Record';

    protected array $defaultConfig = [];

    protected string $defaultTemplatesDirectory = '';

    protected ?string $customTemplatesDirectory = null;

    protected array $config = [];

    /**
     * @var string[]
     */
    protected array $tableNames = [];

    protected \PDO $pdo;

    protected string $destinationDirectory = '';

    protected string $loadedCollectionTemplateFile = '';

    protected string $loadedModelTemplateFile = '';

    protected string $loadedRecordTemplateFile = '';

    protected array $filesToWrite = [];

    public function __construct(array $config) {

        $ds = DIRECTORY_SEPARATOR;
        $this->defaultTemplatesDirectory = realpath(__DIR__ . "{$ds}..{$ds}templates{$ds}");

        $this->defaultConfig = require __DIR__ . "{$ds}..{$ds}sample-config.php";
        $this->config = $config;

        if(!array_key_exists('pdo', $this->config)) {

            throw new \Exception('`pdo` entry is missing in config!');

        } elseif (!is_array($this->config['pdo'])) {

            throw new \Exception('`pdo` entry in config is not an array!');
        }

        $this->pdo = new \PDO(...$this->config['pdo']);

        // fill config with defaults
        foreach ($this->defaultConfig as $key=>$val) {

            if(!array_key_exists($key, $this->config)) {

                $this->config[$key] = $val;
            }
        }

        if(!is_string($this->config['directory'])) {

            throw new \Exception('`directory` entry in config is not a string!');
        }

        $this->destinationDirectory  = $this->config['directory'];

        if(OtherUtils::isNonEmptyString($this->config['custom_templates_directory'])) {

            if(!FileIoUtils::isDir($this->config['custom_templates_directory'])) {

                throw new \Exception('`custom_templates_directory` entry in config is not a valid directory!');
            } 

            $this->customTemplatesDirectory = $this->config['custom_templates_directory'];
        }

        if(!is_array($this->config['tables_to_skip'])) {

            $this->config['tables_to_skip'] = [];
        }

        if(!OtherUtils::isNonEmptyString($this->config['collection_class_to_extend'])) {

            $this->config['collection_class_to_extend'] = static::DEFAULT_COLLECTION_CLASS_TO_EXTEND;
        }

        if(!OtherUtils::isNonEmptyString($this->config['model_class_to_extend'])) {

            $this->config['model_class_to_extend'] = static::DEFAULT_MODEL_CLASS_TO_EXTEND;
        }

        if(!OtherUtils::isNonEmptyString($this->config['record_class_to_extend'])) {

            $this->config['record_class_to_extend'] = static::DEFAULT_RECORD_CLASS_TO_EXTEND;
        }

        if(
            $this->config['created_timestamp_column_name'] !== null
            && !OtherUtils::isNonEmptyString($this->config['created_timestamp_column_name'])
        ) {
            $this->config['created_timestamp_column_name'] = null;
        }

        if(
            $this->config['updated_timestamp_column_name'] !== null
            && !OtherUtils::isNonEmptyString($this->config['updated_timestamp_column_name'])
        ) {
            $this->config['updated_timestamp_column_name'] = null;
        }

        if(!is_callable($this->config['table_name_to_record_class_prefix_transformer'])) {

            $this->config['table_name_to_record_class_prefix_transformer'] =
                    $this->defaultConfig['table_name_to_record_class_prefix_transformer'];
        }

        if(!is_callable($this->config['table_name_to_collection_and_model_class_prefix_transformer'])) {

            $this->config['table_name_to_collection_and_model_class_prefix_transformer'] =
                    $this->defaultConfig['table_name_to_collection_and_model_class_prefix_transformer'];
        }
    }

    public function __invoke() : ?int {

        try {
            return $this->loadTableNames()
                ?? $this->loadTemplateFiles()
                ?? $this->generateClassFiles()
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

    protected function generateClassFiles(): ?int {

        echo "Generating class files ....". PHP_EOL;

        foreach ($this->tableNames as $tableName) {

            echo "\tGenerating class files for table `{$tableName}` ....". PHP_EOL;

            $collectionOrModelNamePrefix = 
                $this->config['table_name_to_collection_and_model_class_prefix_transformer']($tableName);

            $recordNamePrefix =
                $this->config['table_name_to_record_class_prefix_transformer']($tableName);

            $collectionClassName = $collectionOrModelNamePrefix. 'Collection.php';
            $modelClassName = $collectionOrModelNamePrefix. 'Model.php';
            $recordClassName = $recordNamePrefix. 'Record.php';

            echo "\t\tCollection class file name: `{$collectionClassName}`". PHP_EOL;
            echo "\t\tModel class file name: `{$modelClassName}`". PHP_EOL;
            echo "\t\tRecord class file name: `{$recordClassName}`". PHP_EOL;

            $destinationDirectory = 
                FileIoUtils::concatDirAndFileName($this->destinationDirectory, $collectionOrModelNamePrefix);

            echo "\tClass files for table `{$tableName}` will be written to `{$destinationDirectory}`". PHP_EOL;

            $this->filesToWrite[$destinationDirectory] = [];

            $this->filesToWrite[$destinationDirectory][$collectionClassName]
                = $this->generateCollectionClassFile($tableName, $collectionOrModelNamePrefix, $recordNamePrefix);

            $this->filesToWrite[$destinationDirectory][$modelClassName]
                = $this->generateModelClassFile($tableName, $collectionOrModelNamePrefix, $recordNamePrefix);

            $this->filesToWrite[$destinationDirectory][$recordClassName]
                = $this->generateRecordClassFile($tableName, $collectionOrModelNamePrefix, $recordNamePrefix);

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

    protected function generateColNamesAsPhpDocClassProperties(string $tableName): string {

        $colDefs = SchemaUtils::fetchTableColsFromDB($tableName, $this->pdo);
        $props = '';

        foreach ($colDefs as $col) {

            $coltype = $col->type;

            $unsigned = '';
            if (substr(strtoupper($coltype), -9) == ' UNSIGNED') {

                $unsigned = substr($coltype, -9);
                $coltype = substr($coltype, 0, -9);
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

        echo "Creating generated collection, model & record class files ....". PHP_EOL;

        foreach ($this->filesToWrite as $modelDirectory => $modelFilesInfo) {

            $this->mkdir($modelDirectory);

            foreach ($modelFilesInfo as $fileName => $fileContents) {

                $destinationFile = FileIoUtils::concatDirAndFileName($modelDirectory, $fileName);

                if(FileIoUtils::isFile($destinationFile)) {

                    echo "Skipping creation of `{$destinationFile}`, it already exists!" . PHP_EOL;
                    continue;
                }

                echo "Creating `{$destinationFile}`!" . PHP_EOL;

                FileIoUtils::put($destinationFile, $fileContents);
            }

            echo PHP_EOL;
        }

        $fullDestination = realpath($this->destinationDirectory);
        echo PHP_EOL . "Done creating all collection, model & record class files. " . PHP_EOL
            . "They are all located in `{$fullDestination}`." . PHP_EOL
            . "Goodbye!" . PHP_EOL . PHP_EOL;

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

    protected function loadTableNames(): ?int {

        echo 'Getting a list of database tables to generate collection, model & record classes for .....' . PHP_EOL . PHP_EOL;
        echo 'Database Info:' . PHP_EOL;
        echo SchemaUtils::getCurrentConnectionInfo($this->pdo) . PHP_EOL;

        $tableNames = SchemaUtils::fetchTableListFromDB($this->pdo);

        sort($tableNames);

        foreach($tableNames as $tableName) {

            if(
                (
                    strtolower(SchemaUtils::getPdoDriverName($this->pdo)) === 'sqlite'
                    && 
                    ( 
                        in_array(strtolower($tableName), static::LIST_OF_SQLITE_TABLES_TO_SKIP)
                        || str_contains(strtolower($tableName), 'sqlite')
                    )
                ) // sqlite filteration
                || in_array($tableName, $this->config['tables_to_skip']) // user specified filteration
            ) {
                echo "Skipping table `{$tableName}`" . PHP_EOL;
                continue; // don't add this table
            }

            echo "Adding table `{$tableName}`" . PHP_EOL;
            $this->tableNames[] = $tableName;

        } // foreach($tableNames as $tableName)

        echo PHP_EOL .'Finished getting a list of database tables to generate collection, model & record classes for!' . PHP_EOL . PHP_EOL;

        return null;
    }
}
