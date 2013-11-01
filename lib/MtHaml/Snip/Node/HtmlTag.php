<?php

namespace MtHaml\Snip\Node;


use MtHaml\Node\Tag;
use MtHaml\NodeVisitor\NodeVisitorInterface;
use MtHaml\Node\NestAbstract;
use MtHaml\Snip\NodeVisitor\PhpRenderer;

class HtmlTag extends Tag
{
    protected $content;

    public function __construct(array $position, $content)
    {
        parent::__construct($position,'',array());
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getNodeName()
    {
        return 'htmltag';
    }

    public function accept(NodeVisitorInterface $visitor)
    {
        if ($visitor instanceof PhpRenderer) {
            $visitor->enterHtmlTag($this);
            if ($this->hasChilds()){
                $visitor->indent();
                $this->visitChilds($visitor);
                $visitor->undent();
            }
        }else{
            $this->visitChilds($visitor);
        }
    }


}

