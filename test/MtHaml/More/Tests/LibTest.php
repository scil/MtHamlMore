<?php

namespace MtHaml\More\Tests;

use MtHaml\More\Lib;

/**
 *
 */
class LibTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider filesArray
     */
    public function testPaeseFiles($expectedFiles, $files)
    {
       $parsedFiles= Lib::parseFiles($files,false);
        $expectedFiles = str_replace(array('\\'), array('/'), $expectedFiles);
        $parsedFiles = str_replace(array('\\'), array('/'), $parsedFiles);
        $this->assertEquals($expectedFiles, $parsedFiles);
    }


    public function filesArray()
    {
        return array(
           'simple' => array(array('1', '2'), array('1', '2')),
            'simple' => array( array(__DIR__ . '/LibsTest.php'),array(__DIR__ . '/LibsTest.php')),
            'glob' => array(
                array(__DIR__ . '/NodeVisitor/ApplyPlaceholderValueTest.php',
                    __DIR__ . '/NodeVisitor/PhpRendererTest.php'
                ),
                array(__DIR__ . '/NodeVisitor/*.php')
            ),
            'glob and normal' => array(
                array(__DIR__ . '/NodeVisitor/ApplyPlaceholderValueTest.php',
                    __DIR__ . '/NodeVisitor/PhpRendererTest.php',
                    __DIR__ . '/LibsTest.php'
                ),
                array(__DIR__ . '/NodeVisitor/*.php',
                    __DIR__ . '/LibsTest.php'
                )
            ),
        );
    }

   /**
    *  check file exists
    * @dataProvider filesArray2
    */
    public function testParseFiles2($expectedFiles,$files)
    {
        $this->setExpectedException(
            'Exception', "file no exists ${files[0]}"
        );
        Lib::parseFiles($files);

    }
    function filesArray2()
    {
        return array(
            'simple' => array(array('1', '2'), array('1', '2')),
        );
    }

}
