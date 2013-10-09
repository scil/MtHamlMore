<?php

namespace MtHaml\Node;

use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Snip\Node\VirtualParentInterface;

abstract class NodeAbstract
{
    private $position;
    private $parent;
    private $nextSibling;
    private $previousSibling;

    public function __construct(array $position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getLineno()
    {
        return $this->position['lineno'];
    }

    public function getColumn()
    {
        return $this->position['column'];
    }

    protected function setParent(NodeAbstract $parent = null)
    {
        $this->parent = $parent;
    }

    public function hasParent()
    {
        return null !== $this->parent;
    }

    //hack
    public function getParent()
    {
//        return $this->parent;
        $node=$this->parent;
        try{
             while ($node instanceof VirtualParentInterface){
                $node=$node->getRealNode()->parent;
             }
        }catch(\Exception $e) {}
        return $node;
    }

    abstract public function getNodeName();

    abstract public function accept(NodeVisitorInterface $visitor);

    protected function setNextSibling(NodeAbstract $node = null)
    {
        $this->nextSibling = $node;
    }

    //hack
    public function getNextSibling()
    {
//        return $this->nextSibling;
        $node=$this;
        $next=$this->nextSibling;
        try{
            while (is_null($next) && ($node->parent instanceof VirtualParentInterface)){
                $node=$this->parent->getRealNode();
//                printf("next: \$node is %o\n",$node);
                $next=$node->nextSibling;
            }
        }catch(\Exception $e) {}
        return $next;
    }

    protected function setPreviousSibling(NodeAbstract $node = null)
    {
        $this->previousSibling = $node;
    }

    //hack
    public function getPreviousSibling()
    {
//        return $this->previousSibling;
        $node=$this;
        $pre=$this->previousSibling;
        try{
            while (is_null($pre) && ($node->parent instanceof VirtualParentInterface)){
                $node=$this->parent->getRealNode();
//                printf('$node %s',$node->name);
                $pre=$node->previousSibling;
            }
        }catch(\Exception $e) {}
        return $pre;
    }

    public function isConst()
    {
        return false;
    }
}

