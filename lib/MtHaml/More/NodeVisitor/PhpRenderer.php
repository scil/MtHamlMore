<?php

namespace MtHaml\More\NodeVisitor;

use MtHaml\Exception;
use MtHaml\More\Node\HtmlTag;
use MtHaml\More\Node\SnipCaller;
use MtHaml\More\Node\PlaceholderValue;
use MtHaml\More\Node\Placeholder;
use MtHaml\More\Environment;
use MtHaml\Node\Tag;


class PhpRenderer extends \MtHaml\NodeVisitor\PhpRenderer implements VisitorInterface
{

/*
    %input(selected)
        rendered by MtHaml:
             <?php echo MtHaml\Runtime::renderAttributes(array(array('selected', TRUE)), 'html5', 'UTF-8'); ?>
    %input(selected=true)
        rendered by MtHaml:
             <?php echo MtHaml\Runtime::renderAttributes(array(array('selected', true)), 'html5', 'UTF-8'); ?>
    %script{:type => "text/javascript", :src => "javascripts/script_#{2 + 7}"}
        rendered by MtHaml:
            <?php echo MtHaml\Runtime::renderAttributes(array(array('title', 'title'), array('href', href)), 'html5', 'UTF-8'); ?>
        attributes_dyn: type is not dyn; but src is
    %a(title="title" href=href) Stuff
        rendered by MtHaml:
            <?php echo MtHaml\Runtime::renderAttributes(array(array('type', 'text/javascript'), array('src', ('javascripts/script_' . (2 + 7)))), 'html5', 'UTF-8'); ?>
        attributes_dyn: title is not dyn, but href is
    %span.ok(class="widget_#{widget.number}")
        rendered by MtHaml:
            <?php echo MtHaml\Runtime::renderAttributes(array(array('class', 'ok'), array('class', ('widget_' . (widget.number)))), 'html5', 'UTF-8'); ?>

TODO:
    %div.add{:class => [@item.type, @item == @sortcol && [:sort, @sortdir]] } Contents
        rendered by MtHaml:
             <?php echo MtHaml\Runtime::renderAttributes(array(array('class', 'add'), array('class', ([@item.type, @item == @sortcol && [:sort, @sortdir]]))), 'html5', 'UTF-8'); ?>
*/
    protected function renderDynamicAttributes(Tag $tag)
    {
        $oldOutput = $this->output;
        $oldLineNo = $this->lineno;
        parent::renderDynamicAttributes($tag);
        $newOutput = substr($this->output, strlen($oldOutput));
        // <attrs> like: 'title',(title)),array('href',href),array('id',id
        $re = '@^ <\?php echo MtHaml\\\\Runtime::renderAttributes\(array\(array\((?<attrs>.+)\)\), \'(?<format>\w+)\', \'(?<charset>[-\w]+)\'\); \?>$@';
        if (preg_match($re, $newOutput, $matches)) {
            $str_attrs = $matches['attrs'];
            $format=$matches['format'];
            $charset = $matches['charset'];
            if (strpos($str_attrs, 'AttributeInterpolation') === false && strpos($str_attrs, 'AttributeList') === false) {
                $str_attrs = explode('), array(', $str_attrs);
                $attributes = array();
                $attributes_dyn = array();
                foreach ($str_attrs as $str_attr) {
                    list ($name, $value) = explode(', ', $str_attr);
                    $name=trim($name,"'");

                    if ((substr($value,0,1))=="'"){
                        if( !isset($attributes_dyn[$name]) || $attributes_dyn[$name]==false){
                            $attributes_dyn[$name]=false;
                        }
                    }
                    else{
                        $attributes_dyn[$name]=true;
                    }

                    if ('data' === $name) {
                    } else if ('id' === $name) {
                    } else if ('class' === $name) {
                        if (isset($attributes['class'])) {
                            $attributes['class'][]=  $value;
                        } else {
                            $attributes['class'] = array($value);
                        }
                    } else if ('TRUE' === strtoupper($value)) {
                        if ('html5' === $format) {
                            $attributes[$name] = true;
                        } else {
                            $attributes[$name] = $name;
                        }
                    } else if (false === $value || null === $value) {
                        // do not output
                    } else {
                        if (isset($attributes[$name])) {
                            // so that next assignment puts the attribute
                            // at the end for the array
                            unset($attributes[$name]);
                        }
                        $attributes[$name] = $value;
                    }
                }

                if(isset($attributes_dyn['class'])){
                    if($attributes_dyn['class']==true){
                        /*
                          %span.ok(class="widget_#{widget.number}")
                            ->
                          implode(' ',array('ok',('widget_' . (widget.number)))
                        */
                        $attributes['class']='implode(\' \',array('.implode(',',$attributes['class']).')';
                    }else{
                        $attributes['class']=implode(' ',$attributes['class']);
                    }
                }

                $result = null;
                foreach ($attributes as $name => $value) {

                    if($attributes_dyn[$name]==false) $value=trim($value,"'");

                    if (null !== $result) {
                        $result .= ' ';
                    }
                    if ($value instanceof AttributeInterpolation) {
                        $result .= $value->value;
                    } else if (true === $value) {
                        $result .=
                            htmlspecialchars($name, ENT_QUOTES, $charset);
                    } else {
                        $result .=
                            htmlspecialchars($name, ENT_QUOTES, $charset)
                            .(
                                $attributes_dyn[$name]?
                                    "=\"<?php echo htmlspecialchars($value, ENT_QUOTES, $charset); ?>\"" :
                                    '="'.htmlspecialchars($value, ENT_QUOTES, $charset).'"'
                            );
                    }
                }
                $this->output = $oldOutput .' '. $result;
                $this->lineno = $oldLineNo + substr_count($result, "\n");

            }

        }

    }

    public function enterHtmlTag(HtmlTag $node)
    {
        $indent = $this->shouldIndentBeforeOpen($node);
        $this->write(sprintf('%s', $node->getContent()), $indent, true);
    }

    public function enterSnipCaller(SnipCaller $node)
    {
    }

    public function enterSnipCallerContent(SnipCaller $node)
    {
    }

    public function leaveSnipCallerContent(SnipCaller $node)
    {
    }

    public function enterSnipCallerChilds(SnipCaller $node)
    {
    }

    public function leaveSnipCallerChilds(SnipCaller $node)
    {
    }

    public function leaveSnipCaller(SnipCaller $node)
    {
    }


    public function enterPlaceholderValue(PlaceholderValue $node)
    {
    }

    public function enterPlaceholderValueContent(PlaceholderValue $node)
    {
    }

    public function leavePlaceholderValueContent(PlaceholderValue $node)
    {
    }

    public function enterPlaceholderValueChilds(PlaceholderValue $node)
    {
    }

    public function leavePlaceholderValueChilds(PlaceholderValue $node)
    {
    }

    public function leavePlaceholderValue(PlaceholderValue $node)
    {
    }

    public function enterPlaceholder(Placeholder $node)
    {
    }

    public function leavePlaceholder(Placeholder $node)
    {
    }

    public function enterPlaceholderContent(Placeholder $node)
    {
    }

    public function leavePlaceholderContent(Placeholder $node)
    {
    }

    public function enterPlaceholderChilds(Placeholder $node)
    {
    }

    public function leavePlaceholderChilds(Placeholder $node)
    {
    }

}
