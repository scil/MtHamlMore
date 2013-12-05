<?php

namespace MtHaml\More\Exception;

use MtHaml\Exception;

class SnipHouseException extends MoreException
{
    function __construct($file,$name,$msg)
    {
        parent::__construct("SNIP [[$name]] in file [[$file]] : $msg");
    }
}
