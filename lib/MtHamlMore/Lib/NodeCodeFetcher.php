<?php

namespace MtHamlMore\Lib;

use MtHaml\Exception;


class NodeCodeFetcher
{
    private $code;
    private $tokenToStartOffset = array();
    private $tokenToEndOffset = array();

    public function __construct($code)
    {
        $this->code = $code;

        $tokens = token_get_all($code);
        $offset = 0;
        foreach ($tokens as $pos => $token) {
            if (is_array($token)) {
                $len = strlen($token[1]);
            } else {
                $len = strlen($token); // not 1 due to b" bug
            }

            $this->tokenToStartOffset[$pos] = $offset;
            $offset += $len;
            $this->tokenToEndOffset[$pos] = $offset;
        }
    }

    public function getNodeCode(\PHPParser_Node $node)
    {
        $startPos = $node->getAttribute('startOffset');
        $endPos = $node->getAttribute('endOffset');
        if ($startPos === null || $endPos === null) {
            return ''; // just to be sure
        }

        $startOffset = $this->tokenToStartOffset[$startPos];
        $endOffset = $this->tokenToEndOffset[$endPos];
        return substr($this->code, $startOffset, $endOffset - $startOffset);
    }
}

