<?php

namespace MtHaml\Snip\Node;


use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;
use MtHaml\Snip\NodeVisitor\VisitorInterface;

class SnipCaller extends NestAbstract
{
    protected $snipName;
    protected $env;
    protected $attributes;

    public function __construct(array $position, $snipName, $env, array $attributes)
    {
        parent::__construct($position);
        $this->snipName = $snipName;
        $this->env= $env;
        $this->attributes = $attributes;
    }

    public function getSnipName()
    {
        return $this->snipName;
    }
    public function getEnv()
    {
        return $this->env;
    }


    public function hasAttributes()
    {
        return 0 < count($this->attributes);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }


    public function getNodeName()
    {
        return 'snipcaller';
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        // only accept visitors which implement NodeVisitor\Snip\VisitorInterface
       if (!$visitor instanceof VisitorInterface)
        return;

        if ($visitor instanceof \MtHaml\Snip\NodeVisitor\PhpRenderer) {
            $visitor->enterSnipCaller($this);
            $visitor->leaveSnipCaller($this);
         }
        else{
            // $visitor is MakesurePlaceholderValue or  ApplyPlaceholderValue   .
            // when ApplyPlaceholderValue is needed? sed example "snip in snip 2"

            $visitor->enterSnipCaller($this);
            if (false !== $visitor->enterSnipCallerChilds($this)) {
                $this->visitChilds($visitor);
            }
            $visitor->leaveSnipCallerChilds($this);
            $visitor->leaveSnipCaller($this);
        }



    }
}

