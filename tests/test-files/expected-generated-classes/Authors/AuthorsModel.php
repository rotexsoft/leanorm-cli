<?php
declare(strict_types=1);

namespace App\Models\Authors;

/**
 * @method AuthorsCollection createNewCollection(\GDAO\Model\RecordInterface ...$list_of_records)
 * @method AuthorRecord createNewRecord(array $col_names_n_vals = [])
 * @method ?AuthorRecord fetchOneRecord(?object $select_obj=null, array $relations_to_include=[])
 * @method AuthorRecord[] fetchRecordsIntoArray(?object $select_obj=null, array $relations_to_include=[])
 * @method AuthorRecord[] fetchRecordsIntoArrayKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[])
 * @method AuthorsCollection fetchRecordsIntoCollection(?object $select_obj=null, array $relations_to_include=[])
 * @method AuthorsCollection fetchRecordsIntoCollectionKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[])
 */
class AuthorsModel extends \LeanOrm\CachingModel {
    
    protected ?string $collection_class_name = AuthorsCollection::class;
    
    protected ?string $record_class_name = AuthorRecord::class;
    
    protected ?string $created_timestamp_column_name = 'date_created';
    
    protected ?string $updated_timestamp_column_name = 'm_timestamp';
    
    protected string $primary_col = 'author_id';
    
    protected string $table_name = 'authors';
    
    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) {
        $this->table_cols = include(__DIR__ . DIRECTORY_SEPARATOR . 'AuthorsFieldsMetadata.php');
        
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        
        // Define relationships below here
        
        //$this->belongsTo(...)
        //$this->hasMany(...);
        //$this->hasManyThrough(...);
        //$this->hasOne(...)
    }
}
