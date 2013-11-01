<?php

namespace MtHaml\Snip\Tests\NodeVisitor;

use MtHaml\Snip\Snip\SnipHouse;


class SnipHouseTest extends \PHPUnit_Extensions_Story_TestCase
{
    static public $snipsDir;
    static function setUpBeforeClass(){
        self::$snipsDir =  __DIR__.'/fixtures/sniphouse';
    }
    static function unifyPath($path){
        return str_replace('\\','/',$path);
    }

    /**
     * @scenario
     */
    public function addUses()
    {
        $this->given('new Snip')
            ->when('add one use file',self::$snipsDir . '/scil.php')
            ->when('add use files', array( self::$snipsDir.'/ivy.php', self::$snipsDir .'/scil.php'))
            ->then('uses len', 2)
            ->then("first use", self::$snipsDir .'/scil.php')
            ->then("last use", self::$snipsDir .'/ivy.php')
            ->then("main file is ", null)
            ->then("get snip and use files",'name','scil',self::$snipsDir .'/scil.php',array())

            ->when('add one use file',self::$snipsDir.'/dora.php')
            ->then("no mixes for file",self::$snipsDir.'/dora.php')
            ->then("get snip and use files",'name','dora',self::$snipsDir .'/dora.php',array())
            ->then("there are mixes for file", self::$snipsDir.'/dora.php')
            ->then("file's mixes",self::$snipsDir.'/dora.php',array(self::$snipsDir.'/common.php'))

            ->then("get snip and use files",'common_name','common',self::$snipsDir .'/common.php',array())

            ->when('add main file',self::$snipsDir.'/ivy.php')
            ->then("main file is ", self::$snipsDir.'/ivy.php')
            ->then("get snip and use files",'name','ivy',self::$snipsDir .'/ivy.php',array())
            ->then('uses len', 2)
            ->then("first use", self::$snipsDir .'/dora.php')
            ;
    }

    public function runGiven(&$world, $action, $arguments)
    {
        switch($action)
        {
            case "new Snip":
            {
                $world['S'] = new SnipHouse;
                $mixes =   new \ReflectionProperty($world['S'],'file_mixes');
                $mixes->setAccessible(true);
                $world['mixes'] = $mixes;
            }
            break;
            default:
            {
                return $this->notImplemented($action);
            }
        }
    }

    public function runWhen(&$world, $action, $arguments)
    {
        switch($action)
        {
            case 'add one use file':
            {
                try
                {
                    $world['S']->addUse($arguments[0]);
                }
                catch (Exception $ex)
                {
                    $world['errors'][] = $ex->getMessage();
                }
            }
            break;
            case 'add use files':
            {
                try
                {
                    $world['S']->addUses($arguments[0]);
                }
                catch (Exception $ex)
                {
                    $world['errors'][] = $ex->getMessage();
                }
            }
            break;
            case 'add main file':
            {
                try
                {
                    $world['S']->setMainFile($arguments[0]);
                }
                catch (Exception $ex)
                {
                    $world['errors'][] = $ex->getMessage();
                }
            }
            break;
            default:
            {
                return $this->notImplemented($action);
            }
        }
    }

    public function runThen(&$world, $action, $arguments)
    {
        switch($action)
        {
            case "uses len":
            {
                $this->assertEquals($arguments[0], count($world['S']->getUses()) );
            }
            break;
            case "first use":
            {
                $this->assertEquals(self::unifyPath($arguments[0]), reset($world['S']->getUses()) );
            }
            break;
            case "last use":
            {
                $this->assertEquals(self::unifyPath($arguments[0]), end($world['S']->getUses()) );
            }
            break;
            case "main file is ":
            {
                $this->assertEquals(self::unifyPath($arguments[0]), $world['S']->getMainFile() );
            }
            break;
            case "get snip and use files":
            {
                list($snip,$file,$uses)= $world['S']->getSnipAndFiles($arguments[0]);
                $this->assertEquals($arguments[1],$snip);
                $this->assertEquals(self::unifyPath($arguments[2]),$file);
                $this->assertEquals($arguments[3],$uses);
            }
            break;
            case "no mixes for file":
            {
                $mixes = $world['mixes'] ;
                $mixes = $mixes->getValue($world['S']);
                $this->assertArrayNotHasKey(self::unifyPath($arguments[0]),$mixes);

            }
                break;
            case "there are mixes for file":
            {
                $mixes = $world['mixes'] ;
                $mixes = $mixes->getValue($world['S']);
                $this->assertArrayHasKey(self::unifyPath($arguments[0]),$mixes);

            }
            break;
            case "file's mixes":
            {
                $mixes = $world['mixes'] ;
                $mixes = $mixes->getValue($world['S']);
                $this->assertEquals(self::unifyPath($arguments[1]),$mixes[self::unifyPath($arguments[0])]);

            }
                break;
            default:
            {
                return $this->notImplemented($action);
            }
        }
    }
}
