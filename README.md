[![Run PHP Tests and Code Quality Tools](https://github.com/rotexsoft/leanorm-cli/actions/workflows/php.yml/badge.svg)](https://github.com/rotexsoft/leanorm-cli/actions/workflows/php.yml) &nbsp; 
![GitHub release (latest SemVer)](https://img.shields.io/github/v/release/rotexsoft/leanorm-cli) &nbsp; 
![GitHub](https://img.shields.io/github/license/rotexsoft/leanorm-cli) &nbsp; 
[![Coverage Status](https://coveralls.io/repos/github/rotexsoft/leanorm-cli/badge.svg)](https://coveralls.io/github/rotexsoft/leanorm-cli) &nbsp; 
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/rotexsoft/leanorm-cli) &nbsp; 
![Packagist Downloads](https://img.shields.io/packagist/dt/rotexsoft/leanorm-cli) &nbsp; 
![GitHub top language](https://img.shields.io/github/languages/top/rotexsoft/leanorm-cli) &nbsp; 
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/rotexsoft/leanorm-cli) &nbsp; 
![GitHub commits since latest release (by date)](https://img.shields.io/github/commits-since/rotexsoft/leanorm-cli/latest) &nbsp; 
![GitHub last commit](https://img.shields.io/github/last-commit/rotexsoft/leanorm-cli) &nbsp; 
![GitHub Release Date](https://img.shields.io/github/release-date/rotexsoft/leanorm-cli) &nbsp; 
<a href="https://libraries.io/github/rotexsoft/leanorm-cli">
    <img alt="Libraries.io dependency status for GitHub repo" src="https://img.shields.io/librariesio/github/rotexsoft/leanorm-cli">
</a>

# LeanOrm Cli

## About

This is a simple command line tool for creating [LeanOrm](https://github.com/rotexsoft/leanorm) Collection, Model & Record classes for each table & view in a specified database. This tool reads information about tables and views from the db & uses that information to generate the desired earlier mentioned classes.

## Installation

> composer require --dev rotexsoft/leanorm-cli


## Usage

> php ./vendor/bin/generate-orm-classes.php /path/to/config.php

You need to create a config file that will be passed to the command above. 
This config file must return an array with the minimum structure below:

```php
return [
    'pdo' => [             // An array with a minimum of 1 item and a maximum of 4 items
        'sqlite::memory:', // 1st compulsory item is a dsn string to be passed as 1st arg to the PDO consructor
        'username',        // 2nd optional item is a username string to be passed as 2nd arg to the PDO consructor
        'password',        // 3rd optional item is a password string to be passed as 3rd arg to the PDO consructor
        [],                // 4th optional item is an options array to be passed as 4th arg to the PDO consructor
    ],                                                              
    
    'namespace' => null,              // Root Namespace classes will belong to. E.g. 'App\\DataSource'. Null means no namespace.
    'directory' => './model-classes', // Absolute or relative path to where classes are to be written
];
```
See [sample-config.php](sample-config.php) for the full structure of the array that should be returned by the config file. 
Each item in the sample config file is thoroughly described & you can specify the ones you want in your own config file.

> **Note:** running the command multiple times will not lead to previously generated classes being overwritten. If you want classes to be regenerated, you would have to manually delete them before re-running the command.

The classes generate will have the directory structure below for a database with an **authors** table & a **posts** table:

```
/path
    /where
        /classes
            /were
                /written
                |_________Authors
                |           |______AuthorsCollection.php    # Collection class for the authors table
                |           |______AuthorsModel.php         # Model class for the authors table 
                |           |______AuthorRecord.php         # Record class for the authors table 
                |
                |_________Posts
                            |______PostsCollection.php    # Collection class for the posts table
                            |______PostsModel.php         # Model class for the posts table 
                            |______PostRecord.php         # Record class for the posts table 
```

Most of these classes will be empty, and are provided so you can extend their behavior if you wish. They also serve to assist IDEs with autocompletion of some typehints.

## Custom Templates

You can override the templates used by this tool and provide your own instead. This lets you customize the code generation; for example, to add your own common methods or to extend intercessory classes.

The templates used by this tool are located [here](templates), you can look at them to hve an idea of how to craft your custom templates. Your custom templates can be located in any directory / folder of your choosing but they must have the same names as the default template files, i.e: 

- TypesCollection.php.tpl
- TypesModel.php.tpl
- TypeRecord.php.tpl

You do not have to override all the template files, you can just override the ones you want to customize, the ones you do not ovverride will keep using the default template(s). For example, you may only want to override the Model template **TypesModel.php.tpl**, which will lead to the default Collection & Record templates to continue being used, while your custom Model template would be used for creating Model classes.

You will need to specify the directory containing your custom template files in the config file earlier described by adding an item with the key **custom_templates_directory** like so:



```php
return [
    'pdo' => [             // An array with a minimum of 1 item and a maximum of 4 items
        'sqlite::memory:', // 1st compulsory item is a dsn string to be passed as 1st arg to the PDO consructor
        'username',        // 2nd optional item is a username string to be passed as 2nd arg to the PDO consructor
        'password',        // 3rd optional item is a password string to be passed as 3rd arg to the PDO consructor
        [],                // 4th optional item is an options array to be passed as 4th arg to the PDO consructor
    ],                                                              
    
    'namespace' => null,              // Root Namespace classes will belong to. E.g. 'App\\DataSource'. Null means no namespace.
    'directory' => './model-classes', // Absolute or relative path to where classes are to be written
    'custom_templates_directory' => './path', // Absolute / relative path to a location containing 1 or more template files below
                                              // TypesModel.php.tpl, TypesCollection.php.tpl & TypeRecord.php.tpl
];
```

Below are a full list of variables / tokens that are present in the template files:

- **{{{COLLECTION_EXTENDED}}}** will be substituted with the fully qualified class name of the collection each new collection class will extend. Default is **\LeanOrm\Model\Collection**
- **{{{MODEL_EXTENDED}}}** will be substituted with the fully qualified class name of the model each new model class will extend. Default is **\LeanOrm\Model**
- **{{{RECORD_EXTENDED}}}** will be substituted with the fully qualified class name of the record each new record class will extend. Default is **\LeanOrm\Model\Record**

- **{{{CREATED_TIMESTAMP_COLUMN_NAME}}}** will be substituted with NULL or name of the db column that will be timestamped each time a new record is inserted into the db
- **{{{UPDATED_TIMESTAMP_COLUMN_NAME}}}** will be substituted with NULL or name of the db column that will be timestamped each time a record is saved to the db

- **{{{DB_COLS_AS_PHP_CLASS_PROPERTIES}}}** will be substituted with a partial docblock of db field names for a Record

- **{{{NAME_SPACE}}}** will be subsituted with an empty string or a specified namespace name with **\\{{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}** appended to it

- **{{{MODEL_OR_COLLECTION_CLASS_NAME_PREFIX}}}** will be subsituted with a pluralized camel-cased version of **{{{TABLE_NAME}}}**
- **{{{RECORD_CLASS_NAME_PREFIX}}}** will be subsituted with a singularized camel-cased version of **{{{TABLE_NAME}}}**

- **{{{PRIMARY_COL_NAME}}}** will be subsituted with an empty string or the name of the primary key column in the table **{{{TABLE_NAME}}}**
- **{{{TABLE_NAME}}}** will be subsituted with the name of the db table that we are generating collection, model & record classes for
