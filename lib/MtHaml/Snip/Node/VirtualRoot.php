<?php

namespace MtHaml\Snip\Node;


use MtHaml\Node\Root;

class VirtualRoot extends Root implements VirtualParentInterface
{
    private $realNode;
    function __construct($realNode,$position=null)
    {
        parent::__construct($position);
        $this->realNode=$realNode;

    }
    public function getRealNode()
    {
        return $this->realNode;
    }
    public function getNodeName()
    {
        return 'virtualroot';
    }
}
