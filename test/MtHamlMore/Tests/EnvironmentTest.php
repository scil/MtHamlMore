<?php

namespace MtHamlMore\Tests;

use MtHaml\Tests\TestCase;

require_once ROOT_DIR. '/vendor/mthaml/mthaml/test/MtHaml/Tests/TestCase.php';

class EnvironmentTest extends TestCase
{
    /** @dataProvider getEnvironmentTests */
    public function testEnvironment($file)
    {
        $parts = $this->parseTestFile($file);

        // for mtahml's tests, skip twig, change target php to php_more
        $php = $parts['FILE'];
        if(defined('TEST_MTHAML') && TEST_MTHAML === true){
            if( strpos($php,"new MtHaml\\Environment('twig',")!==false ) return;
            $php = str_replace("new MtHaml\\Environment('php',","new MtHamlMore\\Environment('php_more',",$php);
        }

        file_put_contents($file . '.php', $php);

        file_put_contents($file . '.haml', $parts['HAML']);
        if(isset($parts['SNIPS'])){
            file_put_contents($file . '.snip', $parts['SNIPS']);
        }
        if(isset($parts['SNIPS2'])){
            file_put_contents($file . '2.snip', $parts['SNIPS2']);
        }
        file_put_contents($file . '.exp', $parts['EXPECT']);

        try {
            ob_start();
            require $file . '.php';
            $out = ob_get_clean();
        } catch(\Exception $e) {
            $this->assertException($parts, $e);
            $this->cleanup($file);
            return;
        }
        $this->assertException($parts);

        file_put_contents($file . '.out', $out);

        $this->assertSame($parts['EXPECT'], $out);

        $this->cleanup($file);
    }

    protected function cleanup($file)
    {
        if (file_exists($file . '.out')) {
            unlink($file . '.out');
        }
        unlink($file . '.haml');
        unlink($file . '.php');
        if (file_exists($file . '.snip'))  unlink($file . '.snip');
        if (file_exists($file . '2.snip'))  unlink($file . '2.snip');
        unlink($file . '.exp');
    }

    public function getEnvironmentTests()
    {
        if (false !== $tests = getenv('ENV_TESTS')) {
            $files = explode(' ', $tests);
        } else {

            if(defined('TEST_MTHAML') && TEST_MTHAML === true){
                $files =
                    array_merge(
                        glob(__DIR__ . '/fixtures/environment/*.test'),
                        glob(ROOT_DIR . '/vendor/mthaml/mthaml/test/MtHaml/Tests/fixtures/environment/*.test')
                    );
            }
            else{
                $files = glob(__DIR__ . '/fixtures/environment/*.test');
            }
        }
        return array_map(function($file) {
            return array($file);
        }, $files);
    }

}


