<?php

namespace MtHaml\Snip\Node;

use MtHaml\NodeVisitor\NodeVisitorInterface;

class SecondPlaceholder extends Placeholder implements SecondInterface
{
    private $first;
    public function __construct($first)
    {
        parent::__construct(array(),null);
        $this->first=$first;
    }
    public function getFirst()
    {
        return $this->first;
    }
    public function getNodeName()
    {
        return 'virtualplaceholder';
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        $this->visitChilds($visitor);
    }
}
