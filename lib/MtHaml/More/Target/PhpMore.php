<?php

namespace MtHaml\More\Target;

use MtHaml\Target\Php;
 use MtHaml\More\NodeVisitor\PhpRenderer;
 use MtHaml\More\Environment;
 use MtHaml\More\Parser;

class PhpMore extends Php
{

    function __construct(array $options = array())
    {
        $this->setParserFactory(
            function(Environment $env, array $options) {
                return new Parser($env);
            });
        $this->setRendererFactory(
            function(Environment $env, array $options) {
                return new PhpRenderer($env);
            });
        parent::__construct($options);
    }
}

