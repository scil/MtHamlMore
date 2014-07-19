<?php

namespace MtHamlMore\Node;


use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;
use MtHamlMore\NodeVisitor\ApplyPlaceholderValue;
use MtHamlMore\NodeVisitor\MakesurePlaceholderValue;

class Placeholder extends NestAbstract implements FirstInterface
{
    protected $name;

    protected $second;

    public function __construct(array $position, $name)
    {
        parent::__construct($position);
        $this->name = $name;
    }


    function setSecond(SecondInterface $v)
    {
        $this->second=$v;
    }
    function hasSecond()
    {
        return !!($this->second);
    }
    function getSecond()
    {
        return $this->second;
    }
    public function visitSecond(NodeVisitorInterface $visitor)
    {
        if ($this->second)
            $this->second->accept($visitor);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNodeName()
    {
        return 'Placeholder';
    }

    public function accept(NodeVisitorInterface $visitor)
    {
//        if ($visitor instanceof VisitorAbstract )  {
        if ($visitor instanceof MakesurePlaceholderValue || $visitor instanceof ApplyPlaceholderValue ) {
            $visitor->enterPlaceholder($this);
        } else {
            $this->visitSecond($visitor);
        }

    }


}

