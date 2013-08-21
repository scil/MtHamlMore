<?php

namespace MtHaml\Snip\Node;


use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;
use MtHaml\Snip\NodeVisitor\ApplyPlaceholderValue;
use MtHaml\Snip\NodeVisitor\MakesurePlaceholderValue;

class Placeholder extends NestAbstract
{
    protected $name = null;

    public  $values = null;


    public function visitValues(NodeVisitorInterface $visitor)
    {

        foreach ($this->values as $child) {
            $child->accept($visitor);
        }
    }

    public function __construct(array $position, $name)
    {
        parent::__construct($position);
        $this->name = $name;
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
            $this->visitValues($visitor);
        }

    }


}

