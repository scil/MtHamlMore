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

    TODO:
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

        %div.add{:class => [@item.type, @item == @sortcol && [:sort, @sortdir]] } Contents
            rendered by MtHaml:
                 <?php echo MtHaml\Runtime::renderAttributes(array(array('class', 'add'), array('class', ([@item.type, @item == @sortcol && [:sort, @sortdir]]))), 'html5', 'UTF-8'); ?>
    */
    protected function renderDynamicAttributes(Tag $tag)
    {
        $oldOutput = $this->output;
        $oldLineNo = $this->lineno;
        parent::renderDynamicAttributes($tag);
        $newOutput = substr($this->output, strlen($oldOutput) + 1); // why '+1'? the first char is a space
        $parser = $this->env->php_parser;
        try {
            $stmts = $parser->parse($newOutput);
            if (is_array($stmts) && (($echo = $stmts[0]) instanceof \PHPParser_Node_Stmt_Echo)) {
                if (($staticCall = $echo->exprs[0]) && ($staticCall->name == 'renderAttributes')) {
                    $args = $staticCall->args;
                    $o_attris = $args[0]->value->items;
                    $attributes = array();
                    foreach ($o_attris as $attri) {
                        $attributes[$attri->value->items[0]->value->value] = $attri->value->items[1]->value->name->parts[0];
                    }
                    $format = $args[1]->value->value;
                    $charset = $args[2]->value->value;
                }


                $result = null;
                foreach ($attributes as $name => $value) {
                    if (null !== $result) {
                        $result .= ' ';
                    }
                    if ($value instanceof AttributeInterpolation) {
                        $result .= $value->value;
                    } else if ('true' === strtolower($value) ) {
                        $result .=
                            htmlspecialchars($name, ENT_QUOTES, $charset);
                    } else {
                        $result .=
                            htmlspecialchars($name, ENT_QUOTES, $charset)
                            . '="'
                            . htmlspecialchars($value, ENT_QUOTES, $charset)
                            . '"';
                    }
                }
                $this->output = $oldOutput . ' ' . $result;
                $this->lineno = $oldLineNo + substr_count($result, "\n");
            }
        } catch (PHPParser_Error $e) {
            echo 'Parse Error: ', $e->getMessage();
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
