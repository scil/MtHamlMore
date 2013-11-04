<?php

namespace MtHaml\Snip\NodeVisitor;

use MtHaml\Node\NestInterface;
use MtHaml\Snip\Exception\SyntaxErrorException;
use MtHaml\Snip\Node\Placeholder;
use MtHaml\Snip\Node\PlaceholderDefaultCaller;
use MtHaml\Snip\Node\SecondPlaceholder;
use MtHaml\Snip\Node\SecondPlaceholderDefaultValueCaller;
use MtHaml\Snip\Node\SnipCaller;

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
            $default = $this->getDefault($node);
            if ($default){
                $this->setValues($node,$default);
            }
            else
                throw new SyntaxErrorException(sprintf('plz supply values for placeholder [[%s]]', $node->getName()));
        }

    }
    /*
     * return Array
     */
    protected function getDefault($node)
    {
        if ($node->hasChilds())
            return $node->getChilds();
        elseif ($node->hasContent())
            return array($node->getContent());
    }
    protected function setValues(Placeholder $node,$v){
        // when "_\n  v", $v is an array already, but "_ v" not
        if(!is_array($v)){
            $v=array($v);
        }

        $this->checkDefaultCallers($node,$v);

        $second = new SecondPlaceholder($node);
        foreach($v as $child){
            $second->addChild($child);
        }
        $node->setSecond($second);
    }
    /*
     * @param $nodes [array]
     */
    protected function checkDefaultCallers($node,$nodes)
    {
        $default = $this->getDefault($node);
        if ($default){
            $callers=$this->getDefaultCallers($nodes);
            foreach($callers as $caller){
                foreach($default as $child){
                    // in node tree ,one node only has one position
                    $child2=clone $child;
                    $caller->addChild($child2);
                }
            }
        }

    }
    /*
     * _body
     *   @@default           //get
     *   .ok
     *     @@default         //get
     *     @anotherSnip
     *       @@default       //no
     *     @thirdSnip
     *       _body
     *         @@default     //no
     *   @@default           //yes
     */
    protected function getDefaultCallers($nodes)
    {
        $callers=[];
        foreach($nodes as $node){
           if ($node instanceof PlaceholderDefaultCaller)
               $callers[]=$node;
           elseif($node instanceof NestInterface && ! $node instanceof SnipCaller && $node->hasChilds() )
               array_merge($callers,$this->getDefaultCallers($node->getChilds()));
        }
        return $callers;

    }


}
