<?php


namespace MtHamlMore\Snip;

interface SnipHouseInterface
{
    public function getSnipAndFiles($name, array $arg = array());
}