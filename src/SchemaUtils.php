<?php
declare(strict_types=1);

namespace LeanOrmCli;

use Aura\SqlSchema\ColumnFactory;
use Atlas\Info\Info as AtlasInfo;
use Atlas\Pdo\Connection as AtlasPdoConnection;

/**
 * Description of SchemaUtils
 *
 * @author rotimi
 */
class SchemaUtils {
    
    /**
     * @return mixed[]|string[]
     */
    public static function fetchTableListFromDB(\PDO $pdo): array {
        
        if(strtolower(static::getPdoDriverName($pdo)) === 'sqlite') {
            
            // Do this to return both tables and views
            // static::getSchemaQueryingObject()->fetchTableList()
            // only returns table names but no views. That's why
            // we are doing this here
            return static::dbFetchCol(
                $pdo,   
                "SELECT name FROM sqlite_master
                UNION ALL
                SELECT name FROM sqlite_temp_master
                ORDER BY name"
            );
        }
        
        $schema = static::getSchemaQueryingObject($pdo);
        
        if(strtolower(static::getPdoDriverName($pdo)) ===  'pgsql') {
            
            // Calculate schema name for postgresql
            $schemaName = static::dbFetchValue($pdo, 'SELECT CURRENT_SCHEMA');
            
            return $schema->fetchTableList($schemaName);
        }
        
        return $schema->fetchTableList();
    }
    
    /**
     * @return  mixed[]|\Aura\SqlSchema\Column[]
     */
    public static function fetchTableColsFromDB(string $table_name, \PDO $pdo): array {
                
        if(strtolower(static::getPdoDriverName($pdo)) ===  'pgsql') {
            
            // Use Atlas Info to get this data for Postgresql because 
            // Aura Sql Schema keeps blowing up when fetchTableCols
            // is called on \Aura\SqlSchema\PgsqlSchema
            $info = AtlasInfo::new(AtlasPdoConnection::new($pdo));
            
            $columnsInfo = $info->fetchColumns($table_name);

            foreach ($columnsInfo as $key=>$columnInfo) {

                // Convert each row to objects because 
                // static::getSchemaQueryingObject()->fetchTableCols(..)
                // returns an array of Aura\SqlSchema\Column objects.
                // Converting each row to an object will allow for each
                // row's data to be accessible via object property syntax
                $columnsInfo[$key] = (object)$columnInfo;
            }
             
            return $columnsInfo;
        }
        
        // This works so far for mysql & sqlite.  
        // Will need to test what works for MS Sql Server
        return static::getSchemaQueryingObject($pdo)->fetchTableCols($table_name);
    }
    
    public static function columnExistsInDbTable(string $table_name, string $column_name): bool {
        
        $schema_definitions = static::fetchTableColsFromDB($table_name);
        
        return array_key_exists($column_name, $schema_definitions);
    }
    
    public static function getPdoDriverName(\PDO $pdo): string {
        
        return $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }
    
    protected static function getSchemaQueryingObject(\PDO $pdo): \Aura\SqlSchema\AbstractSchema {
        
        // a column definition factory 
        $columnFactory = new ColumnFactory();
        $pdoDriverName = static::getPdoDriverName($pdo);

        $schemaClassName = '\\Aura\\SqlSchema\\' . ucfirst($pdoDriverName) . 'Schema';

        // the schema discovery object
        return new $schemaClassName($pdo, $columnFactory);
    }
    
    protected static function dbFetchCol(\PDO $pdo, string $query): array {
        
        $statement = $pdo->prepare($query);
        $statement->execute();
        
        return $statement->fetchAll(\PDO::FETCH_COLUMN, 0);
    }
    
    /**
     * @return mixed
     */
    protected static function dbFetchValue(\PDO $pdo, string $query) {
        
        $statement = $pdo->prepare($query);
        $statement->execute();
        
        return $statement->fetchColumn(0);
    }
}
