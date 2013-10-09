<?php

namespace MtHaml\Snip\Node;


use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;
use MtHaml\Snip\NodeVisitor\ApplyPlaceholderValue;
use MtHaml\Snip\NodeVisitor\MakesurePlaceholderValue;

class Placeholder extends NestAbstract
{
    protected $name;

    protected $virtual;

    public function __construct(array $position, $name)
    {
        parent::__construct($position);
        $this->name = $name;
    }


    function setVirtual(VirtualPlaceholder $v)
    {
        $this->virtual=$v;
    }
    function hasVirtual()
    {
        return !!($this->virtual);
    }
    function getVirtual()
    {
        return $this->virtual;
    }
    public function visitVirtual(NodeVisitorInterface $visitor)
    {
        if ($this->virtual)
            $this->virtual->accept($visitor);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getNodeName()
    {
        return 'placeholder';
    }

    public function accept(NodeVisitorInterface $visitor)
    {
//        if ($visitor instanceof VisitorAbstract )  {
        if ($visitor instanceof MakesurePlaceholderValue || $visitor instanceof ApplyPlaceholderValue ) {
            $visitor->enterPlaceholder($this);
        } else {
            $this->visitVirtual($visitor);
        }

    }


}

