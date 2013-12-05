<?php

namespace MtHaml\More\Node;


use MtHaml\NodeVisitor\NodeVisitorInterface;

interface FirstInterface
{
    function setSecond(SecondInterface $v);
    function hasSecond();
    function getSecond();
    public function visitSecond(NodeVisitorInterface $visitor);
}

