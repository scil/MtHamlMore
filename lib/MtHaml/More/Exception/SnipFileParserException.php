<?php

namespace MtHaml\More\Exception;

use MtHaml\Exception;

class SnipFileParserException extends MoreException
{
    function __construct($file,$name,$msg)
    {
        parent::__construct("SNIP [[$file]][[$name]] : $msg");
    }
}
