<?php

namespace MtHaml\Snip\Target;

use MtHaml\Target\Php;
 use MtHaml\Snip\NodeVisitor\PhpRenderer;
 use MtHaml\Snip\Environment;
 use MtHaml\Snip\Parser;

class PhpSnip extends Php
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

