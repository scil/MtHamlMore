<?php


namespace MtHaml\Snip\Snip;

interface SnipHouseInterface
{
    public function getSnipAndFiles($name, array $arg = array());
}