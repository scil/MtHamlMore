<?php

namespace MtHaml\Snip\Tests;


/**
 *
 */
class ExampleTest extends \PHPUnit_Framework_TestCase
{

    static public $exampleDir;
    static public $expectedOutputFile;
    static function setUpBeforeClass(){
        self::$exampleDir= ROOT_DIR.'/examples';
        self::$expectedOutputFile = __DIR__.'/fixtures/example_output';
    }

    function test()
    {

        $hamlfile=self::$exampleDir . '/php.haml';

        $compiled = compilePhpSnipHaml( file_get_contents($hamlfile),array(
            'uses'=>array(self::$exampleDir.'/snips/php.php'),
            'filename'=>$hamlfile,
            'prepare'=>true,
            'enable_escaper' => false,
        ));

        $this->assertEquals(file_get_contents(self::$expectedOutputFile),$compiled);
    }

}
