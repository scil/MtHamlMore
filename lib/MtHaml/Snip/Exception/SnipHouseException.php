<?php

namespace MtHaml\Snip\Exception;

use MtHaml\Exception;

class SnipHouseException extends SnipException
{
    function __construct($file,$name,$msg)
    {
        parent::__construct("SNIP [[$file]][[$name]] : $msg");
    }
}
