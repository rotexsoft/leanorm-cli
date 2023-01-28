<?php
use \LeanOrmCli\FileIoUtils;

/**
 * Description of FileIoUtilsTest
 *
 * @author rotimi
 */
class FileIoUtilsTest extends \PHPUnit\Framework\TestCase {
        
    public static function setUpBeforeClass(): void {
        
        parent::setUpBeforeClass();
        
        if(
            FileIoUtils::isFile(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-two-lines.txt'
            )
        ) {
            unlink(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-two-lines.txt'
            );
        }
        
        if(
            FileIoUtils::isDir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
                .'dir3'.DIRECTORY_SEPARATOR
            )    
        ) {
            rmdir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
                .'dir3'.DIRECTORY_SEPARATOR
            );
        }
    }
    
    public static function tearDownAfterClass(): void {
        
        parent::tearDownAfterClass();
        
        if(
            FileIoUtils::isDir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
                .'dir3'.DIRECTORY_SEPARATOR
            )    
        ) {
            rmdir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
                .'dir3'.DIRECTORY_SEPARATOR
            );
            rmdir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
            );
            rmdir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
            );
        }
        
        if(
            FileIoUtils::isFile(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-two-lines.txt'
            )
        ) {
            unlink(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-two-lines.txt'
            );
        }
    }
    
    public function testThatGetWorksAsExpected() {
        
        self::assertEquals(
            'some string', 
            FileIoUtils::get(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-one-line.txt' 
            )
        );
    }
        
    public function testThatGetThrowsException() {
        
        $this->expectException(\Exception::class);
        
        FileIoUtils::get('non-existent-file.txt');
    }
        
    public function testThatPutWorksAsExpected() {
        
        self::assertEquals(
            23, 
            FileIoUtils::put(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-two-lines.txt',
                'some string some string'
            )
        );
    }
    
    public function testThatPutThrowsException() {
        
        $this->expectException(\Exception::class);
        
        FileIoUtils::put(
            dirname(__FILE__).DIRECTORY_SEPARATOR
            .'non-existent-directory'.DIRECTORY_SEPARATOR
            .'file-with-two-lines.txt',
            'some string some string'
        );
    }
        
    public function testThatMkdirWorksAsExpected() {
        
        self::assertFalse(
            FileIoUtils::isDir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
                .'dir3'.DIRECTORY_SEPARATOR
            )
        );
        
        FileIoUtils::mkdir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
                .'dir3'.DIRECTORY_SEPARATOR
        );
        
        self::assertTrue(
            FileIoUtils::isDir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'dir1'.DIRECTORY_SEPARATOR
                .'dir2'.DIRECTORY_SEPARATOR
                .'dir3'.DIRECTORY_SEPARATOR
            )
        );
    }
    
    public function testThatMkdirThrowsException() {
        
        $this->expectException(\Exception::class);
        
        FileIoUtils::mkdir(
            dirname(__FILE__).DIRECTORY_SEPARATOR
            .'test-files'
        );
    }
    
    public function testThatIsFileWorksAsExpected() {
        
        self::assertTrue(
            FileIoUtils::isFile(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-one-line.txt'
            )
        );
        
        self::assertFalse(
            FileIoUtils::isFile(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-one-line777.txt'
            )
        );
    }
    
    public function testThatIsDirWorksAsExpected() {
        
        self::assertTrue(
            FileIoUtils::isDir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'
            )
        );
        
        self::assertFalse(
            FileIoUtils::isDir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-one-line.txt'
            )
        );
        
        self::assertFalse(
            FileIoUtils::isDir(
                dirname(__FILE__).DIRECTORY_SEPARATOR
                .'test-files'.DIRECTORY_SEPARATOR
                .'file-with-one-line777.txt'
            )
        );
    }
    
    public function testThatConcatDirAndFileNameWorksAsExpected() {
        
        self::assertEquals(
            '/home' . DIRECTORY_SEPARATOR . 'file', 
            FileIoUtils::concatDirAndFileName(
                '/home/////', 'file'
            )
        );
        
        self::assertEquals(
            '/home' . DIRECTORY_SEPARATOR . 'file', 
            FileIoUtils::concatDirAndFileName(
                "/home\\\\\\", 'file'
            )
        );
    }
}
