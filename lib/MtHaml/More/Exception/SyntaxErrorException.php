<?php

namespace MtHaml\More\Exception;

//this Exception only occures in main haml , not snip haml which should be SnipHouseException or SnipFileParseException.
class SyntaxErrorException extends \MtHaml\Exception\SyntaxErrorException
{
}

