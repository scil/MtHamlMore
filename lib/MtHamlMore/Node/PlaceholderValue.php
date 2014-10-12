<?php

namespace MtHamlMore\Node;


use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;
use MtHamlMore\NodeVisitor\VisitorInterface;

class PlaceholderValue extends NestAbstract
{
    protected $name;
    protected $sInlineContent; // used for SnipCaller attribute

    public function __construct(array $position, $name)
    {
        parent::__construct($position);
        $this->name = $name;
    }
    public function getSInlineContent(){
        return $this->sInlineContent;
    }
    public function hasSInlineContent(){
        return is_string($this->sInlineContent)?true:false;
    }
    public function setSInlineContent($s){
        $this->sInlineContent=$s;
    }

    public function getName()
    {
        return $this->name;
    }


    public function getNodeName()
    {
        return 'PlaceholderValue';
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

