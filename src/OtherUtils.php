<?php
declare(strict_types=1);

namespace LeanOrmCli;

/**
 * Description of OtherUtils
 *
 * @author rotimi
 */
class OtherUtils {
    
    public static function getThrowableAsStr(\Throwable $e, string $eol=PHP_EOL): string {

        $previous_throwable = $e; 
        $message = '';

        do {
            $message .= "Exception / Error Code: {$previous_throwable->getCode()}"
                . $eol . "Exception / Error Class: " . \get_class($previous_throwable)
                . $eol . "File: {$previous_throwable->getFile()}"
                . $eol . "Line: {$previous_throwable->getLine()}"
                . $eol . "Message: {$previous_throwable->getMessage()}" . $eol
                . $eol . "Trace: {$eol}{$previous_throwable->getTraceAsString()}{$eol}{$eol}";
                
            $previous_throwable = $previous_throwable->getPrevious();
        } while( $previous_throwable instanceof \Throwable );
        
        return $message;
    }
    
    /**
     * @param mixed $val value to be checked if is a string & the length is > 0
     */
    public static function isNonEmptyString($val): bool {
        
        if( function_exists('mb_strlen') ) {

            return is_string($val) && \mb_strlen($val, 'UTF-8') > 0;
        }

        return is_string($val) && strlen($val) > 0;
    }
}
