<?php
use \LeanOrmCli\OtherUtils;

/**
 * Description of FileIoUtilsTest
 *
 * @author rotimi
 */
class OtherUtilsTest extends \PHPUnit\Framework\TestCase {

    public function testThatGetWorksAsExpected() {
        
        self::assertTrue(
            OtherUtils::isNonEmptyString(
                ' ' 
            )
        );
        self::assertTrue(
            OtherUtils::isNonEmptyString(
                'file-with-one-line.txt' 
            )
        );
        
        self::assertFalse(
            OtherUtils::isNonEmptyString('')
        );
        self::assertFalse(
            OtherUtils::isNonEmptyString(['yeah'])
        );
        self::assertFalse(
            OtherUtils::isNonEmptyString( function(){ return ''; } )
        );
    }
        
    public function testThatGetThrowableAsStrWorksAsExpected() {
        
        $throwableAssertingLooper = function (\Throwable $e, string $eol, $output): void {
            
            $previous_throwable = $e; 
            $eol = PHP_EOL;

            do {
                self::assertStringContainsString("Exception / Error Code: {$previous_throwable->getCode()}", $output);
                self::assertStringContainsString($eol . "Exception / Error Class: " . \get_class($previous_throwable), $output);
                self::assertStringContainsString($eol . "File: {$previous_throwable->getFile()}", $output);
                self::assertStringContainsString($eol . "Line: {$previous_throwable->getLine()}", $output);
                self::assertStringContainsString($eol . "Message: {$previous_throwable->getMessage()}" . $eol, $output);
                self::assertStringContainsString($eol . "Trace: {$eol}{$previous_throwable->getTraceAsString()}{$eol}{$eol}", $output);

                $previous_throwable = $previous_throwable->getPrevious();
            } while( $previous_throwable instanceof \Throwable );
        };
        
        $exception1 = new \Exception('Exception 1' , 1);
        $exception2 = new \LogicException('Exception 2', 2, $exception1);
        $exception3 = new \BadFunctionCallException('Exception 3', 3, $exception2);
        $exception4 = new \BadMethodCallException('Exception 4', 4, $exception3);    
        
        $throwableAssertingLooper($exception4, PHP_EOL, OtherUtils::getThrowableAsStr($exception4, PHP_EOL));
        $throwableAssertingLooper($exception3, PHP_EOL, OtherUtils::getThrowableAsStr($exception3, PHP_EOL));
        $throwableAssertingLooper($exception2, PHP_EOL, OtherUtils::getThrowableAsStr($exception2, PHP_EOL));
        $throwableAssertingLooper($exception1, PHP_EOL, OtherUtils::getThrowableAsStr($exception1, PHP_EOL));
        
        $error1 = new \Error('Exception 1' , 1);
        $error2 = new \ArithmeticError('Exception 2', 2, $error1);
        $error3 = new \DivisionByZeroError('Exception 3', 3, $error2);
        
        $throwableAssertingLooper($error3, PHP_EOL, OtherUtils::getThrowableAsStr($error3, PHP_EOL));
        $throwableAssertingLooper($error2, PHP_EOL, OtherUtils::getThrowableAsStr($error2, PHP_EOL));
        $throwableAssertingLooper($error1, PHP_EOL, OtherUtils::getThrowableAsStr($error1, PHP_EOL));
    }
}
