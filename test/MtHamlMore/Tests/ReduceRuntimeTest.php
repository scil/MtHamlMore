<?php

namespace MtHamlMore\Tests;

use MtHaml\Tests\TestCase;

require_once ROOT_DIR. '/vendor/mthaml/mthaml/test/MtHaml/Tests/TestCase.php';

class ReduceRuntimeTest extends TestCase
{
    /** @dataProvider getReduceRuntimeTests */
    public function testEnvironment($file)
    {
        $parts = $this->parseTestFile($file);

        // for mtahml's tests, skip twig, change target php to php_more
        $php = $parts['FILE'];

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

        if($parts['EXPECT_PHP'])
            $this->assertSame($parts['EXPECT_PHP'], str_replace("\r\n","\n",file_get_contents($file.'..php')));

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
        if (file_exists($file . '..php'))  unlink($file . '..php');
        unlink($file . '.exp');
    }

    public function getReduceRuntimeTests()
    {
        $files = glob(__DIR__ . '/fixtures/reduce_runtime/*.test') ;
        return array_map(function($file) {
            return array($file);
        }, $files);
    }

}


