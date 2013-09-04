<?php

namespace MtHaml\Snip\NodeVisitor;

use MtHaml\Snip\Exception\SyntaxErrorException;
use MtHaml\Snip\Node\Placeholder;

class ApplyPlaceholderValue extends VisitorAbstract
{
    protected $values;

    function  __construct($v)
    {
        $this->values = $v;
    }

    function enterPlaceholder(Placeholder $node)
    {
        $vs = $this->values;
        $namedValues= $vs[0];
        $unNamedValues=$vs[1];

        $name = $node->getName();

        // normal placeholder
        if(gettype($name)=='integer' && isset($unNamedValues[$name])){
            $v=$unNamedValues[$name];
            $this->setValues($node,$v);
        // named placeholder
        }elseif(isset($namedValues[$name])){
            $v=$namedValues[$name];
            $this->setValues($node,$v);
        // default values
        }else {
            if ($node->hasChilds())
                $this->setValues($node, $node->getChilds() );
            elseif ($node->hasContent())
                $this->setValues($node, array($node->getContent()) );
            else
                throw new SyntaxErrorException(sprintf('plz supply values for placeholder [[%s]]', $node->getName()));
        }

    }
    protected function setValues(Placeholder $node,$v){
        // when "_\n  v", $c is an array already, but "_ v" not
        if(!is_array($v)){
            $v=array($v);
        }
        $node->setValues($v);
    }


}
