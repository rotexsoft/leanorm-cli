<?php
declare(strict_types=1);
namespace LeanOrmCli;

/**
 * This code was lifted from https://github.com/atlasphp/Atlas.Cli/blob/2.x/src/Fsio.php
 */
class FileIoUtils {

    public static function get(string $file): string {
        
        $level = error_reporting(0);
        $result = file_get_contents($file);
        error_reporting($level);

        if ($result !== false) {
            
            return $result;
        }

        $error = error_get_last();
        
        throw new \Exception($error['message']);
    }

    public static function put(string $file, string $data): int {
        
        $level = error_reporting(0);
        $result = file_put_contents($file, $data);
        error_reporting($level);

        if ($result !== false) {
            
            return $result;
        }

        $error = error_get_last();
        
        throw new \Exception($error['message']);
    }

    public static function isFile(string $file): bool {
        
        return file_exists($file) && is_readable($file);
    }

    public static function isDir(string $dir): bool {
        
        return is_dir($dir);
    }

    public static function mkdir(string $dir, int $mode = 0777, bool $deep = true): void {
        
        $level = error_reporting(0);
        $result = mkdir($dir, $mode, $deep);
        error_reporting($level);

        if ($result) { return; }

        $error = error_get_last();
        
        throw new \Exception($error['message']);
    }
    
    public static function concatDirAndFileName(string $dir, string $file): string {

        //trim right-most linux style path separator if any
        $trimedPath = rtrim($dir, '/');

        if( strlen($trimedPath) === strlen($dir) ) {

            //there was no right-most linux path separator
            //try to trim right-most windows style path separator if any
            $trimedPath = rtrim($trimedPath, '\\');
        }
        
        return $trimedPath . DIRECTORY_SEPARATOR . $file;
    }
}
