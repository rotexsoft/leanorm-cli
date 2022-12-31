<?php

return [
    'pdo' => [
        'sqlite::memory:', 
        'username', 
        'password',
    ],
    'namespace' => null,                                            // Root Namespace classes will belong to. E.g. 'App\\DataSource'. Null means no namespace.
    'directory' => './model-classes',                               // absolute or relative path to where classes are to be written
    'custom_templates_directory' => null,                           // absolute or relative path to a direcory containing template files named 
                                                                    // TypesModel.php.tpl, TypesCollection.php.tpl & TypeRecord.php.tpl
    'tables_to_skip' => [],                                         // list of tables to skip generating classes for
    'collection_class_to_extend' => '\\LeanOrm\\Model\\Collection', // class that all collection classes should extend
    'model_class_to_extend' => '\\LeanOrm\\Model',                  // class that all model classes should extend
    'record_class_to_extend' => '\\LeanOrm\\Model\\Record',         // class that all record classes should extend
    'created_timestamp_column_name' => null,                        // name of a column in each table whose value will be updated with the time each row gets inserted
    'updated_timestamp_column_name' => null,                        // name of a column in each table whose value will be updated with the time each row gets updated
    
    'table_name_to_record_class_prefix_transformer' => 
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
        },                                                          // a callable function that converts a db table name into the prefix for a record class name
                
    'table_name_to_collection_and_model_class_prefix_transformer' => 
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
        },                                                          // a callable function that converts a db table name into the prefix for a collection & model class name
];
