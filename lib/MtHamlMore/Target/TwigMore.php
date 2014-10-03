<?php

namespace MtHamlMore\Target;

use MtHaml\Target\Twig;
 use MtHamlMore\NodeVisitor\TwigRenderer;
 use MtHamlMore\Environment;
 use MtHamlMore\Parser;

class TwigMore extends Twig
{

    function __construct(array $options = array())
    {
        $this->setParserFactory(
            function(Environment $env, array $options) {
                return new Parser($env);
            });
        $this->setRendererFactory(
            function(Environment $env, array $options) {
                return new TwigRenderer($env);
            });
        parent::__construct($options);
    }
}

