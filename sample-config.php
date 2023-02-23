<?php
return [
    'pdo' => [                                                          // An array with a minimum of 1 item and a maximum of 4 items or an instance of a PDO object
        'sqlite::memory:',                                              // 1st compulsory item is a dsn string to be passed as 1st arg to the PDO consructor
        'username',                                                     // 2nd optional item is a username string to be passed as 2nd arg to the PDO consructor
        'password',                                                     // 3rd optional item is a password string to be passed as 3rd arg to the PDO consructor
        [],                                                             // 4th optional item is an options array to be passed as 4th arg to the PDO consructor
    ],                                                              
    
    'namespace' => null,                                                // Root Namespace classes will belong to. E.g. 'App\\DataSource'. Null means no namespace.
    'directory' => './model-classes',                                   // Absolute or relative path to where classes are to be written
    'custom_templates_directory' => null,                               // Absolute or relative path to a direcory containing template files named 
                                                                        // TypesModel.php.tpl, TypesCollection.php.tpl & TypeRecord.php.tpl
    'tables_to_skip' => [],                                             // List of tables to skip generating classes for
    'collection_class_to_extend' => '\\LeanOrm\\Model\\Collection',     // Class that all collection classes should extend
    'model_class_to_extend' => '\\LeanOrm\\Model',                      // Class that all model classes should extend
    'record_class_to_extend' => '\\LeanOrm\\Model\\Record',             // Class that all record classes should extend
    'created_timestamp_column_name' => null,                            // Name of a column in each table whose value will be updated with the time each row gets inserted
    'updated_timestamp_column_name' => null,                            // Name of a column in each table whose value will be updated with the time each row gets updated
    
    'add_table_col_metadata_to_trait' => false,                         // if true, a trait containing table metadata info will be generated and referenced in the model class
    
    'table_name_to_record_class_prefix_transformer' =>                  // A callback that accepts a db table name, modifies it & returns the modified value that will be used to substitute {{{RECORD_CLASS_NAME_PREFIX}}} in template files
        function(string $tableName): string {
    
            $inflector = \ICanBoogie\Inflector::get('en');
            $txtSeparatedWithSpaces = $inflector->titleize($tableName);

            if(str_contains($txtSeparatedWithSpaces, ' ')) {

                $words = explode(' ', $txtSeparatedWithSpaces);
                $singularizedWordsCamelCased = '';

                foreach ($words as $word) {

                    $singularizedWordsCamelCased .= 
                        strlen($word) > 1
                            ? $inflector->singularize($word)
                            : $word;
                }

            } else {

                $singularizedWordsCamelCased = $inflector->singularize($txtSeparatedWithSpaces);
            }

            return $singularizedWordsCamelCased;
        },
    
    'table_name_to_collection_and_model_class_prefix_transformer' =>    // A callback that accepts a db table name, modifies it & returns the modified value that will be used to substitute {{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}} in template files
        function(string $tableName): string {

            $inflector = \ICanBoogie\Inflector::get('en');
            $txtSeparatedWithSpaces = $inflector->titleize($tableName);

            if(str_contains($txtSeparatedWithSpaces, ' ')) {

                $words = explode(' ', $txtSeparatedWithSpaces);
                $pluralizedWordsCamelCased = '';

                foreach ($words as $word) {

                    $pluralizedWordsCamelCased .= 
                        strlen($word) > 1
                            ? $inflector->pluralize($word)
                            : $word;
                }

            } else {

                $pluralizedWordsCamelCased = $inflector->pluralize($txtSeparatedWithSpaces);
            }

            return $pluralizedWordsCamelCased;
        },
];
