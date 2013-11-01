<?php

namespace MtHaml\Snip\Node;


use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;
use MtHaml\Snip\NodeVisitor\VisitorInterface;

class PlaceholderValue extends NestAbstract
{
    protected $name;

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
        return 'placeholdercontent';
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        // only accept visitors which implement NodeVisitor\Snip\VisitorInterface
        if (!$visitor instanceof VisitorInterface)
            return;

        if (false !== $visitor->enterPlaceholderValue($this)) {

//            no need to visit content, because ApplyPlaceholderValue has visited
//            if (false !== $visitor->enterPlaceholderValueContent($this)) {
//                $this->visitContent($visitor);
//            }
//            $visitor->leavePlaceholderValueContent($this);

            // sometimes this is needed , see example "snip in snip 2"
            if (false !== $visitor->enterPlaceholderValueChilds($this)) {
                $this->visitChilds($visitor);
            }
            $visitor->leavePlaceholderValueChilds($this);
        }
        $visitor->leavePlaceholderValue($this);
    }
}

