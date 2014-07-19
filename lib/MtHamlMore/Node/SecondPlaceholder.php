<?php

namespace MtHamlMore\Node;

use MtHaml\NodeVisitor\NodeVisitorInterface;

class SecondPlaceholder extends Placeholder implements SecondInterface
{
    private $first;
    public function __construct($first)
    {
        parent::__construct(array(),null);
        $this->first=$first;
    }
    public function hasFirst()
    {
        return !!($this->first);
    }
    public function getFirst()
    {
        return $this->first;
    }
    public function getNodeName()
    {
        return 'SecondPlaceholder';
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        $this->visitChilds($visitor);
    }
}
