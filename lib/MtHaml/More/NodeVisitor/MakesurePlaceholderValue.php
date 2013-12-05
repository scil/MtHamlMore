<?php

namespace MtHaml\More\NodeVisitor;


use MtHaml\More\Exception\SyntaxErrorException;
use MtHaml\More\Node\PlaceholderValue;
use MtHaml\More\Node\SnipCaller;

// placeholdervalue only ocures as SnipCaller's child
class MakesurePlaceholderValue extends VisitorAbstract
{

    function enterPlaceholderValue(PlaceholderValue $node)
    {
        $parent = $node->getParent();

        if (!$parent instanceof SnipCaller) {
            throw new SyntaxErrorException('placeholdervalue must as child of SnipCaller');
        }
    }
    function enterSnipCaller(SnipCaller $node)
    {
        while(($next=$node->getNextSibling()) instanceof PlaceholderValue){
            $node->addChild($next);
        }

        $mustValueType=false;
        if($node->hasChilds()){
            foreach ($node->getChilds() as $child) {
                if($child instanceof PlaceholderValue ){
                    $mustValueType =true;
                }else{
                    if($mustValueType){
                        throw new SyntaxErrorException('if one placeholdervalue child exists, all children of SnipCaller must be placeholdervalue');
                    }
                }
            }

        }
    }
}
