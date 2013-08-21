<?php


namespace MtHaml\Snip\Snip;

interface SnipHouseInterface
{

    public function addUses( $files);
    public function addUse( $file);
    public function getSnipAndFiles($name, array $arg = array());
}