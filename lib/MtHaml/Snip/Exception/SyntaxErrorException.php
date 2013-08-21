<?php

namespace MtHaml\Snip\Exception;

//this Exception only occures in main haml , not snip haml which should be SnipHouseException or SnipFileParseException. see Snip\NodeVisitor\PhpRenderer.php
class SyntaxErrorException extends \MtHaml\Exception\SyntaxErrorException
{
}

