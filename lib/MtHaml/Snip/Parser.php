<?php

namespace MtHaml\Snip;

use MtHaml\Exception;
use MtHaml\Snip\Node\PlaceholderValue;
use MtHaml\Snip\Node\Placeholder;
use MtHaml\Snip\Node\SnipCaller;
use MtHaml\Snip\Node\VirtualRoot;

/**
 * MtHaml Parser
 */
class Parser extends \MtHaml\Parser
{

    protected $unnamedPlaceholderIndex=0;
    // add Envirmonent object, see README.md : Development Rool 2
    public $env;


    public function __construct(\MtHaml\Environment $env=null)
    {
        $snipcaller=$env->getOption('snipcallerNode');
        if(empty($snipcaller))
            parent::__construct();
        else
            $this->parent = new VirtualRoot($snipcaller);

        $this->env=$env;
    }

    protected function parseStatement($buf)
    {
        if (null !== $node = $this->parseSnipCaller($buf)) {

            return $node;

        } else if (null !== $node = $this->parsePlaceholderValue($buf)) {

            return $node;

        } else if (null !== $node = $this->parsePlaceholder($buf)) {

            return $node;

        } else
            return parent::parseStatement($buf);
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

    protected function parseSnipCaller($buf)
    {
        $regex = '/
            @(?P<snip_name>\w+)  # snip name ( @snipname )
            /xA';

        if ($buf->match($regex, $match)) {
            $snip_name = $match['snip_name'];

            $attributes = $this->parseSnipCallerAttributes($buf);

            $node = new SnipCaller($match['pos'][0], $snip_name,$this->env, $attributes);

            $buf->skipWs();

            if (null !== $nested = $this->parseStatement($buf)) {

                $node->setContent($nested);
            }

            return $node;
        }
    }
    protected function parseSnipCallerAttributes($buf){
        return $this->parseTagAttributes($buf);
    }

}

