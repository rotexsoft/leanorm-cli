<?php
declare(strict_types=1);

namespace LeanOrmCli;

/**
 * Description of OrmClassesGenerator
 *
 * @author rotimi
 */
class OrmClassesGenerator {
    
    protected array $defaultConfig = [];
    
    public const LIST_OF_SQLITE_TABLES_TO_SKIP = [
        'sqlite_master',
        'sqlite_sequence',
        'sqlite_stat1',
        'sqlite_schema',
        'sqlite_temp_schema',
        'sqlite_temp_master',
    ];
    
    protected array $config = [];
    
    /**
     * @var string[]
     */
    protected array $tableNames = [];
    
    protected \PDO $pdo;
    
    public function __construct(array $config) {
        
        $ds = DIRECTORY_SEPARATOR;
        $this->defaultConfig = require dirname(__FILE__) . "{$ds}..{$ds}sample-config.php";
        $this->config = $config;
        
        if(!array_key_exists('pdo', $config)) {
            
            throw new \Exception('pdo entry is missing in config!');
            
        } elseif (!is_array($config['pdo'])) {
            
            throw new \Exception('pdo entry in config is not an array!');
        }
        
        $this->pdo = new \PDO(...$config['pdo']);
        
        // fill config with defaults
        foreach ($this->defaultConfig as $key=>$val) {
            
            if(!array_key_exists($key, $this->config)) {
                
                $this->config[$key] = $val;
            }
        }
        
        $this->setTableNames();
    }
    
    public function __invoke() : ?int {
        
        try {
            return
//                $this->setInfo()
//                ?? $this->setDirectory()
//                ?? $this->setNamespace()
//                ?? $this->setTemplates()
//                ?? $this->setTransform()
//                ?? $this->getTypes()
//                ?? $this->putTypes()
            null // delete this line when done
            ;
        } catch (\Exception $e) {
            
            echo OtherUtils::getThrowableAsStr($e);
            
            return 1;
        }
    }
    
    protected function setTableNames(): void {
        
        $tableNames = SchemaUtils::fetchTableListFromDB($this->pdo);
        
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
                continue; // don't add this table
            }
            
            $this->tableNames[] = $tableName;
            
        } // foreach($tableNames as $tableName)
    }
}
