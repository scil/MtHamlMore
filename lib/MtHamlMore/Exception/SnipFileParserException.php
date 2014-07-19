<?php

namespace MtHamlMore\Exception;

use MtHaml\Exception;

class SnipFileParserException extends MoreException
{
    function __construct($file,$name,$msg)
    {
        parent::__construct("SNIP [[$name]] in file [[$file]] : $msg");
    }
}
