<?php

namespace MtHamlMore\Target;

use MtHaml\Target\Php;
 use MtHamlMore\NodeVisitor\PhpRenderer;
 use MtHamlMore\Environment;
 use MtHamlMore\Parser;

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

