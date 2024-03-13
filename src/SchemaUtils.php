<?php
declare(strict_types=1);

namespace LeanOrmCli;

use Rotexsoft\SqlSchema\ColumnFactory;
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
                "SELECT name FROM sqlite_master where sqlite_master.type IN ('table', 'view')
                UNION ALL
                SELECT name FROM sqlite_temp_master where sqlite_temp_master.type IN ('table', 'view')
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
     * @return  mixed[]|\Rotexsoft\SqlSchema\Column[]
     */
    public static function fetchTableColsFromDB(string $table_name, \PDO $pdo): array {
        
        // This works so far for mariadb, mysql, postgres & sqlite.  
        // Will need to test what works for MS Sql Server
        return static::getSchemaQueryingObject($pdo)->fetchTableCols($table_name);
    }
    
    public static function columnExistsInDbTable(string $table_name, string $column_name, \PDO $pdo): bool {
        
        $schema_definitions = static::fetchTableColsFromDB($table_name, $pdo);
        
        return array_key_exists($column_name, $schema_definitions);
    }
    
    public static function getPdoDriverName(\PDO $pdo): string {
        
        return $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }
    
    public static function getCurrentConnectionInfo(\PDO $pdo): string {
        
        
        $info = AtlasInfo::new(AtlasPdoConnection::new($pdo));
        $result = "Currently selected database schema: `{$info->fetchCurrentSchema()}`" . PHP_EOL;
        $attributes = [
            'database_server_info' => 'SERVER_INFO',
            'driver_name' => 'DRIVER_NAME',
            'pdo_client_version' => 'CLIENT_VERSION',
            'database_server_version' => 'SERVER_VERSION',
            'connection_status' => 'CONNECTION_STATUS',
            'connection_is_persistent' => 'PERSISTENT',
        ];

        foreach ($attributes as $key => $value) {
            
            try {
                
                if( $value !== 'PERSISTENT' ) {
                    
                    $result .= "`{$key}`: " . @$pdo->getAttribute(constant(\PDO::class .'::ATTR_' . $value));
                }
                
            } catch (\Exception) {
                
                $result .= "`{$key}`: " . 'Unsupported attribute for the current PDO driver'.PHP_EOL;
                continue;
            }
            
            if( $value === 'PERSISTENT' ) {

                $result .= "`{$key}`: " . var_export(@$pdo->getAttribute(constant(\PDO::class .'::ATTR_' . $value)), true);

            }
            
            $result .= PHP_EOL;
        }

        return $result;
    }
    
    protected static function getSchemaQueryingObject(\PDO $pdo): \Rotexsoft\SqlSchema\AbstractSchema {
        
        // a column definition factory 
        $columnFactory = new ColumnFactory();
        $pdoDriverName = static::getPdoDriverName($pdo);

        $schemaClassName = '\\Rotexsoft\\SqlSchema\\' . ucfirst($pdoDriverName) . 'Schema';

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
