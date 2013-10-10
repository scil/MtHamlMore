<?php

namespace MtHaml\Node;

use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Snip\Node\FirstInterface;
use MtHaml\Snip\Node\SecondInterface;

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
             while ($node instanceof SecondInterface){
                $node=$node->getFirst()->parent;
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
            while (is_null($next) && (($parent=$node->parent) instanceof SecondInterface)){
                $node=$parent->getFirst();
                $next=$node->nextSibling;
                while($next instanceof FirstInterface){
                    if ($next->hasSecond()){
                        $childs = $next->getSecond()->getChilds();
                        $next=$childs[0];
                    }
                }
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
            while (is_null($next) && (($parent=$node->parent) instanceof SecondInterface)){
                $node=$parent->getFirst();
                $pre=$node->previousSibling;
                while($pre instanceof FirstInterface){
                    if ($pre->hasSecond()){
                        $childs = $pre->getSecond()->getChilds();
                        $pre=end($childs);
                    }
                }
            }
        }catch(\Exception $e) {}
        return $pre;
    }

    public function isConst()
    {
        return false;
    }
}

