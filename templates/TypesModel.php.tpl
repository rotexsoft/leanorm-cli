<?php
declare(strict_types=1);

{{{NAME_SPACE}}}

/**
 * @method {{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}Collection createNewCollection(\GDAO\Model\RecordInterface ...$list_of_records)
 * @method {{{RECORD_CLASS_NAME_PREFIX}}}Record createNewRecord(array $col_names_n_vals = [])
 * @method ?{{{RECORD_CLASS_NAME_PREFIX}}}Record fetchOneRecord(?object $select_obj=null, array $relations_to_include=[])
 * @method {{{RECORD_CLASS_NAME_PREFIX}}}Record[] fetchRecordsIntoArray(?object $select_obj=null, array $relations_to_include=[])
 * @method {{{RECORD_CLASS_NAME_PREFIX}}}Record[] fetchRecordsIntoArrayKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[])
 * @method {{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}Collection fetchRecordsIntoCollection(?object $select_obj=null, array $relations_to_include=[])
 * @method {{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}Collection fetchRecordsIntoCollectionKeyedOnPkVal(?\Aura\SqlQuery\Common\Select $select_obj=null, array $relations_to_include=[])
 */
class {{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}Model extends {{{MODEL_EXTENDED}}} {
    
    {{{USE_TRAIT}}}
    
    protected ?string $collection_class_name = {{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}Collection::class;
    
    protected ?string $record_class_name = {{{RECORD_CLASS_NAME_PREFIX}}}Record::class;
    
    protected ?string $created_timestamp_column_name = {{{CREATED_TIMESTAMP_COLUMN_NAME}}};
    
    protected ?string $updated_timestamp_column_name = {{{UPDATED_TIMESTAMP_COLUMN_NAME}}};
    
    protected string $primary_col = '{{{PRIMARY_COL_NAME}}}';
    
    protected string $table_name = '{{{TABLE_NAME}}}';
    
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
