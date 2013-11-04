<?php

namespace MtHaml\Snip\Node;


use MtHaml\Node\NodeAbstract;
use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;

class PlaceholderDefaultCaller extends NestAbstract
{

    public function __construct(array $position)
    {
        parent::__construct(array());
    }


    public function getNodeName()
    {
        return 'placeholderdefaultvaluecaller';
    }

    public function accept(NodeVisitorInterface $visitor)
    {

        if ($visitor instanceof \MtHaml\Snip\NodeVisitor\PhpRenderer) {
            $this->visitChilds($visitor);
         }

    }
}

