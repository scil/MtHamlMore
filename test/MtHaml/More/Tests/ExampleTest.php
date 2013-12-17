<?php

namespace MtHaml\More\Tests;


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

        $compiled = \MtHaml\More\Entry::compilePhpMoreHaml( file_get_contents($hamlfile),
            array(
                'enable_escaper' => false,
            ),
            array(
                'uses'=>array(self::$exampleDir.'/snips/php.php'),
                'filename'=>$hamlfile,
                'prepare'=>true,
        ));

        $this->assertEquals(file_get_contents(self::$expectedOutputFile),$compiled);
    }

}
