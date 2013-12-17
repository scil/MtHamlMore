<?php

namespace MtHaml\More;

use MtHaml\Exception;
use MtHaml\More\Node\HtmlTag;
use MtHaml\More\Node\PlaceholderDefaultCaller;
use MtHaml\More\Node\PlaceholderValue;
use MtHaml\More\Node\Placeholder;
use MtHaml\More\Node\SnipCaller;
use MtHaml\More\Node\SecondRoot;

/**
 * MtHaml Parser
 */
class Parser extends \MtHaml\Parser
{

    protected $unnamedPlaceholderIndex = 0;
    // add Envirmonent object, see README.md : Development Rool 2
    public $env;
    public $currentMoreEnv;


    public function __construct(\MtHaml\Environment $env = null)
    {
        if (empty($env->currentMoreEnv))
            parent::__construct();
        else {
            $this->currentMoreEnv=$env->currentMoreEnv;
            $snipcaller = $this->currentMoreEnv['snipcallerNode'];
            if (empty($snipcaller))
                parent::__construct();
            else
                $this->parent = new SecondRoot($snipcaller);

        }
        $this->env = $env;
    }

    protected function parseStatement($buf)
    {
        if (empty($this->currentMoreEnv)){
            return parent::parseStatement($buf);
        }
        if (null !== $node = $this->parseSnipCaller($buf)) {

            return $node;

        } else if (null !== $node = $this->parsePlaceholderValue($buf)) {

            return $node;

        } else if (null !== $node = $this->parsePlaceholder($buf)) {

            return $node;

        } else if (null !== $node = $this->parseHtmlTag($buf)) {

            return $node;
        } else if (null !== $node = $this->parsePlaceholderDefaultValueCaller($buf)) {

            return $node;
        } else
            return parent::parseStatement($buf);
    }

    protected function parsePlaceholderDefaultValueCaller($buf)
    {
        $regex = '/^@@default$/';
        if ($buf->match($regex, $match)) {
            $node = new PlaceholderDefaultCaller($match['pos'][0]);
            return $node;
        }
    }

    protected function parsePlaceholderValue($buf)
    {

        $regex = '/
            _(?P<name>\w+)?  # placeholder with an optional name ( _name )
            /xA';

        if ($buf->match($regex, $match)) {
            $name = array_key_exists('name', $match) ? $match['name'] : '';


            $node = new PlaceholderValue($match['pos'][0], $name);

            $buf->skipWs();

            if (null !== $nested = $this->parseStatement($buf)) {
//            if (null !== $nested = $this->parseNestableStatement($buf)) {

                $node->setContent($nested);
            }

            return $node;
        }

    }

    protected function parsePlaceholder($buf)
    {
        $regex = '/
            @@@(?P<name>\w+)?  # explicit tag name ( %tagname )
            /xA';

        if ($buf->match($regex, $match)) {
            $name = array_key_exists('name', $match) ? $match['name'] : ($this->unnamedPlaceholderIndex++);

            $node = new Placeholder($match['pos'][0], $name);

            $buf->skipWs();

            if (null !== $nested = $this->parseStatement($buf)) {

                $node->setContent($nested);
            }

            return $node;
        }
    }

    protected function parseHtmlTag($buf)
    {
        $regex = '@
        ^<!--\[if[\w\s]+\]>$| # ie condition comment like <!--[if lt IE9]>
        ^<\w+[^>/]+>$ # start tag which maybe has childs; there are exceptions like <hr> <meta ..>
                   # not included:
                   # end tag like </div>
                   # comment ,except ie condition comment
                   # self-closed tag like <hr/>
                   # single line tag like <h1>title</h1>
                        # they have no childs, ther are parsed as Statement like official MtHaml
        @xA';
        if ($buf->match($regex, $match)) {
            $node = new HtmlTag($match['pos'][0], $match[0]);
            return $node;
        }
    }

    protected function parseSnipCaller($buf)
    {
        $regex = '/
            @(?P<snip_name>\w+)  # snip name ( @snipname )
            /xA';

        if ($buf->match($regex, $match)) {
            $snip_name = $match['snip_name'];

            $attributes = $this->parseSnipCallerAttributes($buf);

            $node = new SnipCaller($match['pos'][0], $snip_name, $this->env->currentMoreEnv, $attributes);

            $buf->skipWs();

            if (null !== $nested = $this->parseStatement($buf)) {

                $node->setContent($nested);
            }

            return $node;
        }
    }

    protected function parseSnipCallerAttributes($buf)
    {
        return $this->parseTagAttributes($buf);
    }

}

