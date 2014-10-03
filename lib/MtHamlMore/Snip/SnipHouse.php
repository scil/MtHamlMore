<?php

namespace MtHamlMore\Snip;

use MtHaml\Exception;
use MtHamlMore\Exception\SnipHouseException;
use MtHamlMore\Lib\File;

class SnipHouse implements SnipHouseInterface
{
    protected static $allSnips = array();
    protected static $file_ueses = array();
    protected static $file_mixes = array();
    protected static $file_snipcaller = array();
    protected $mainFile;
    protected $uses = array();

    function __construct($uses = null, $mainFile = null)
    {
        $this->addUses($uses);

        if ($mainFile) {
            $this->setMainFile($mainFile);
        }

    }

    function setMainFile($file)
    {
        try {
            $this->addUse($file, true);
        } catch (Exception $e) {
            throw new SnipHouseException($file, '', "err: $e when add mainfile $file");
        }
    }

    static function unifyPath($path)
    {
        return str_replace('\\', '/', $path);
    }

    function getMainFile()
    {
        return $this->mainFile;
    }

    function getUses()
    {
        return $this->uses;
    }

    function addUses($files)
    {
        if (empty($files)) return;

        if (is_string($files)) {
            // not only split , but also change str to array
            $files = explode(';', $files);
        }
        // no need to check exists, addFile do this work later
        $files = File::parseFiles($files, false);


        foreach ($files as $file) {
            $this->addUse($file);
        }
    }

    function addUse($file, $bMainFile = false)
    {
        if (empty($file)) {
            return;
        }

        $file = self::unifyPath($file);
        if ($bMainFile) {
            if ($file === $this->mainFile) return;
        }

        // if already in uses, only update order
        if (in_array($file, $this->uses)) {
            $this->removeUse($file);
        }

        if ($bMainFile)
            $this->mainFile = $file;
        else
            array_unshift($this->uses, $file);

    }

    protected function removeUse($file)
    {
        $key = array_search($file, $this->uses);
        if ($key !== false) {
            unset($this->uses[$key]);
        }
    }

    function getSnipAndFiles($name, array $arg = array())
    {

        // First file where snip live
        $allUses = $this->mainFile ? array_merge(array($this->mainFile), $this->uses) : $this->uses;


        if ($found = $this->findSnip($allUses, $name)) {
            list($snip, $file) = $found;
            $uses = isset(static::$file_ueses[$file]) ? static::$file_ueses[$file] : array();
            $snip = call_user_func(self::$file_snipcaller[$file], $snip, $arg, $file);
            return array($snip, $file, $uses);
        } else
            throw new SnipHouseException(implode(';',$allUses), $name, 'not found in there use files ');


    }

    protected function findSnip($files, $snipName)
    {
        $found = false;
        foreach ($files as $file) {
            if (!isset(self::$allSnips[$file])) {
                $this->parseFile($file);
            }
            $filesnips = self::$allSnips[$file];
            if (isset($filesnips[$snipName])) {
                $snip = $filesnips[$snipName];
                $found = true;
                break;
            }
            if (isset(self::$file_mixes[$file])) {
                if ($foundMixes = $this->findSnip(self::$file_mixes[$file], $snipName)) {
                    return $foundMixes;
                }
            }

        }
        return $found ? array($snip, $file) : false;
    }

    protected function parseFile($file)
    {
        if (isset(self::$allSnips[$file]))
            return;


        if (!is_file($file))
            throw new SnipHouseException($file, '', 'file no exist');


        $class = '\MtHamlMore\Snip\SnipFileParser';
        $f = fopen($file, 'r');
        $firstline = fgets($f);
        //        echo '...'.$file.'...<br>';
        if (preg_match('/^-#.*SnipParser="(?<class>[^"]+)"/', $firstline, $matches)) {
            if (is_subclass_of($matches['class'], '\MtHamlMore\SnipFileParserInterface'))
                $class = $matches['class'];
        }

        try {
            $parser = new $class($file);
        } catch (\Exception $e) {
            throw new SnipHouseException($file, '', "file parse wrong $e");
        }
        $snips = $parser->getSnips();
        $uses = $parser->getUses();
        $mixes = $parser->getMixes();
        self::$file_snipcaller[$file] = array($class, 'snipCaller');
        unset($parser);

        if ($uses) {
            if (empty(self::$file_ueses[$file]))
                self::$file_ueses[$file] = $uses;
            else
                self::$file_ueses[$file] += $uses;
        }

        if ($mixes) {
            $mixes = array_map('self::unifyPath', $mixes);
            if (empty(self::$file_mixes[$file]))
                self::$file_mixes[$file] = $mixes;
            else
                self::$file_mixes[$file] += $mixes;
        }

        if (empty(self::$allSnips[$file]))
            self::$allSnips[$file] = $snips;
        else
            self::$allSnips[$file] += $snips;


    }

}
