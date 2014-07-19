<?php
namespace MtHamlMore\Snip;

interface SnipFileParserInterface
{
    // return Array
    public function getSnips();
    // return Array
    public function getUses();
    // return Array
    public function getMixes();
    public static function snipCaller($snip,$arg,$file);
}