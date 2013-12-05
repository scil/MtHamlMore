<?php

namespace MtHaml\More\Node;


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
        return 'PlaceholderDefaultvalueCaller';
    }

    public function accept(NodeVisitorInterface $visitor)
    {

        if ($visitor instanceof \MtHaml\More\NodeVisitor\PhpRenderer) {
            $this->visitChilds($visitor);
         }

    }
}

