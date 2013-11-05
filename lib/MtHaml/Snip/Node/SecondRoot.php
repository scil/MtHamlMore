<?php

namespace MtHaml\Snip\Node;


use MtHaml\Node\Root;

class SecondRoot extends Root implements SecondInterface
{
    private $first;
    function __construct(SnipCaller $first,$position=null)
    {
        parent::__construct($position);
        $this->first=$first;

    }
    public function hasFirst()
    {
        return !!($this->first);
    }
    public function getFirst()
    {
        return $this->first;
    }
    public function getNodeName()
    {
        return 'secondroot';
    }
}
