<?php

namespace MtHamlMore\NodeVisitor;

use MtHaml\Exception;
use MtHamlMore\Node\SnipCaller;
use MtHamlMore\Node\PlaceholderValue;
use MtHamlMore\Environment;

use MtHamlMore\Exception\SnipCallerAttriSyntaxException;
use MtHaml\Node\Text;
use MtHaml\Node\InterpolatedString;

class ApplySnip extends VisitorAbstract
{
    function __construct($indent)
    {
        $this->indent = $indent;
    }


    public function enterSnipCaller(SnipCaller $node)
    {
        $snipName = $node->getSnipName();
        $attributes = $this->parseSnipCallerAttributes($node);
        $placeholdervalues = $this->parseSnipCallerPlaceholderValues($node);

        $env=$node->getEnv();

        $options =   array(
            'placeholdervalues' => $placeholdervalues,
            'baseIndent' =>  $this->indent,
            'level' => $env['level']+1,
            'snipcallerNode'=>$node,
        );

        $secondRoot = Environment::parseSnip($snipName, $attributes, $options ,$env,true);
        $node->setSecond($secondRoot);
    }


    protected function parseSnipCallerPlaceholderValues(SnipCaller $node)
    {
        $namedPlaceholderValues = array();
        $unNamedPlaceholderValues = array();
        if ($node->hasContent()) {
            $unNamedPlaceholderValues[] = $node->getContent();
        } elseif ($node->hasChilds()) {
            if (($childs = $node->getChilds()) && $childs[0] instanceof PlaceholderValue) {
                foreach ($childs as $child) {
                    if ($child->hasContent()) {
                        $value = $child->getContent();
                    } else {
                        $value = $child->getChilds();
                    }
                    if ($placeholderName = $child->getName())
                        $namedPlaceholderValues[$placeholderName] = $value;
                    else
                        $unNamedPlaceholderValues[] = $value;


                }

            } else { //only one
                $unNamedPlaceholderValues[] = $childs;
            }
        }

        return array($namedPlaceholderValues,$unNamedPlaceholderValues);
    }

// development memorandum
//      (123)
//          name: Text(content="123")
//          value: null
//      ("hello world")   // not valid for Html style, ruby style works, see below
//      ("title"=ok)   // not valid for Html style, because Insert is not allowed
//          value: InterpolatedString( childs =array( Text("title") ))
//          value: Insert(content="ok")
//      (title=6+4) // not valid for Snipcaller att, because Insert is not allowed
//          name: Text(content="title")
//          value: Insert(content="6+4")
//       (title=#{6+4})   // not valid for Snipcaller att, because Insert is not allowed
//          name: Text(content="title")
//          value: Insert(content="#{6+4})
//       (title="2012-#{6+4}") // not valid for Snipcaller att, because Insert is not allowed
//          name: Text(content="title")
//          value: InterpolatedString( childs =array( Text("2012-"), Insert("6+4") ))
//      {title}  // not valid for Snipcaller att, because Insert is node allowed
//          name: null
//          value: Insert(content="title")
//       {"title"}
//          name: null
//          value: InterpolatedString ( childs=array( Text("title") )
//      {:title =>"ok"}
//          name: Text
//          value: InterpolatedString ( childs=array( Text("ok") )
//      {:title =>ok} // not valid for Snipcaller att, because Insert is node allowed
//          name: Text
//          value: Insert(content="ok")
//      {"title" => "ok"}
//          name: InterpolatedString
//          value: InterpolatedString ( childs=array( Text("ok") )
//       {"title"=>"2012-#{6+4}"} // not valid for Snipcaller att, because Insert is node allowed
//          name: InterpolatedString ( childs=array( Text("title") )
//          value: InterpolateString( childs =array( Text("2012-"), Insert("6+4") ))

    protected function parseSnipCallerAttributes(SnipCaller $node)
    {

        $normal_attributes = array();
        $named_attributes = array();
        foreach ($node->getAttributes() as $attr) {
            $name = $attr->getName();
            if ($name instanceof Text) {
                // (n=3)  -> n
                // (title)  -> title
                $name_content = $name->getContent();
            } elseif ($name instanceof InterpolatedString) { // @title{"desc"=>"text snip"}
                $name_content = $this->parseInterpolatedString($name);
            } elseif (is_null($name)) {
                // (#{1+1})  this is value of type Insert
                // {"abc"} this is value of InterpolatedString
            } else
                throw new SnipCallerAttriSyntaxException(sprintf('attri value should be must be an instance of InterpolatedString or Text or be ignored , instance of %s given', get_class($value)));

            $value = $attr->getValue();
            if (is_null($value)) {
                $normal_attributes[] = $name_content;
                continue;
            } elseif ($value instanceof InterpolatedString) { // (number="3") (class="ord-#{1+1}")  {"ok"}
                $value_content = $this->parseInterpolatedString($value);

                if (is_null($name)) {
                    $normal_attributes[] = $value_content;
                    continue;
                }
            } else
                throw new SnipCallerAttriSyntaxException(sprintf('attri value should be must be an instance of InterpolatedString  , instance of %s given', get_class($value)));

            $named_attributes[$name_content] = $value_content;

        }

        return array($normal_attributes, $named_attributes);

    }

    protected function parseInterpolatedString(InterpolatedString $node)
    {
        $content = '';
        foreach ($node->getChilds() as $child) {
            if ($child instanceof Text) {
                $content .= $child->getContent();
            } else {
                throw new SnipCallerAttriSyntaxException(sprintf('attri InterpolatedString type value should be made of  Text (InlineSnipCaller is subclass of Text), %s given ', get_class($child)));
            }
        }
        return $content;
    }


}
