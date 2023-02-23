<?php
declare(strict_types=1);

namespace App\Models\VAuthors;

/**
 * @method VAuthorsCollection createNewCollection(\GDAO\Model\RecordInterface ...$list_of_records)
 * @method VAuthorRecord createNewRecord(array $col_names_n_vals = [])
 * @method ?VAuthorRecord fetchOneRecord(?object $select_obj=null, array $relations_to_include=[])
 * @method VAuthorRecord[] fetchRecordsIntoArray(?object $select_obj=null, array $relations_to_include=[])
 * @method VAuthorRecord[] fetchRecordsIntoArrayKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[])
 * @method VAuthorsCollection fetchRecordsIntoCollection(?object $select_obj=null, array $relations_to_include=[])
 * @method VAuthorsCollection fetchRecordsIntoCollectionKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[])
 */
class VAuthorsModel extends \LeanOrm\CachingModel {
    
    use VAuthorsFieldsMetadataTrait;
    
    protected ?string $collection_class_name = VAuthorsCollection::class;
    
    protected ?string $record_class_name = VAuthorRecord::class;
    
    protected ?string $created_timestamp_column_name = 'date_created';
    
    protected ?string $updated_timestamp_column_name = 'm_timestamp';
    
    protected string $primary_col = '';
    
    protected string $table_name = 'v_authors';
    
    public function __construct(
        string $dsn = '', 
        string $username = '', 
        string $passwd = '', 
        array $pdo_driver_opts = [], 
        string $primary_col_name = '', 
        string $table_name = ''
    ) {
        parent::__construct($dsn, $username, $passwd, $pdo_driver_opts, $primary_col_name, $table_name);
        
        // Define relationships below here
        
        //$this->belongsTo(...)
        //$this->hasMany(...);
        //$this->hasManyThrough(...);
        //$this->hasOne(...)
    }
}
