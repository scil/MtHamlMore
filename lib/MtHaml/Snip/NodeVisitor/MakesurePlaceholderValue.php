<?php

namespace MtHaml\Snip\NodeVisitor;


use MtHaml\Snip\Exception\SyntaxErrorException;
use MtHaml\Snip\Node\PlaceholderValue;
use MtHaml\Snip\Node\SnipCaller;

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
