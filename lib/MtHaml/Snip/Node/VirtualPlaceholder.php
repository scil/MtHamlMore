<?php

namespace MtHaml\Snip\Node;

use MtHaml\NodeVisitor\NodeVisitorInterface;

class VirtualPlaceholder extends Placeholder implements VirtualParentInterface
{
    private $realNode;
    public function __construct($realNode)
    {
        parent::__construct(array(),null);
        $this->realNode=$realNode;
    }
    public function getRealNode()
    {
        return $this->realNode;
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
