<?php

namespace MtHaml\More\NodeVisitor;

use MtHaml\Exception;
use MtHaml\More\Exception\MoreException;
use MtHaml\More\Node\HtmlTag;
use MtHaml\More\Node\SnipCaller;
use MtHaml\More\Node\PlaceholderValue;
use MtHaml\More\Node\Placeholder;
use MtHaml\Node\Tag;
use MtHaml\Runtime\AttributeInterpolation;


class PhpRenderer extends \MtHaml\NodeVisitor\PhpRenderer implements VisitorInterface
{
    static private $php_parser;

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
    %div{:class => [$item['type'], $item['urgency']], :id => [$item['type'], $item['number']] }
        rendered by MtHaml:
             <?php echo MtHaml\Runtime::renderAttributes(array(array('class', ([$item['type'], $item['urgency']])), array('id', ([$item['type'], $item['number']]))), 'html5', 'UTF-8'); ?>

    .item{:class => $item['is_empty'] ? "empty":null}
        rendered by MtHaml:
            <?php echo MtHaml\Runtime::renderAttributes(array(array('class', 'item'), array('class', ($item['is_empty'] ? "empty":null))), 'html5', 'UTF-8'); ?>
        %div.add{:class => [$item['type'], $item == $sortcol ? ['sort', $sortdir]:null] } Contents
      writed as->
        %div.add{:class => [$item['type'], $item == $sortcol ? array('sort', $sortdir):null] } Contents
        rendered by MtHaml:
            <?php echo MtHaml\Runtime::renderAttributes(array(array('class', 'add'), array('class', ([$item['type'], $item == $sortcol ? array('sort', $sortdir):null]))), 'html5', 'UTF-8'); ?>
    %a{:href=>"/posts", :data => ['author_id' => 123]} Posts By Author
        rendered by MtHaml:
            <a <?php echo MtHaml\Runtime::renderAttributes(array(array('href', '/posts'), array('data', (['author_id' => 123]))), 'html5', 'UTF-8'); ?>>Posts By Author</a>
    'author_id' => $data_id,'ok' => 3, 'no'=>$data_id+1
        rendered by MtHaml:
             <?php echo MtHaml\Runtime::renderAttributes(array(array('href', '/posts'), array('data', (['author_id' => $data_id,'ok' => 3, 'no'=>$data_id+1]))), 'html5', 'UTF-8'); ?>
    %a{:href=>"/posts", :data => ['author_id' => array('ok'=>3,'no'=>4)]} Posts By Author
        rendered by MtHaml:
            <a <?php echo MtHaml\Runtime::renderAttributes(array(array('href', '/posts'), array('data', (['author_id' => array('ok'=>3,'no'=>4)]))), 'html5', 'UTF-8'); ?>>Posts By Author</a>
    .*/
    protected function renderDynamicAttributes(Tag $tag)
    {
        if ($this->env->noReduceRuntime) {
            parent::renderDynamicAttributes($tag);
            return;
        }
        $oldOutput = $this->output;
        $oldLineNo = $this->lineno;
        parent::renderDynamicAttributes($tag);
        $newOutput = substr($this->output, strlen($oldOutput));
        // <attrs> like: 'title',(title)),array('href',href),array('id',id
        $re = '@^ <\?php echo MtHaml\\\\Runtime::renderAttributes\((?<attrs>array\(array\(.+\)\)), \'(?<format>\w+)\', \'(?<charset>[-\w]+)\'\); \?>$@';
        if (preg_match($re, $newOutput, $matches)) {
            $str_attrs = $matches['attrs'];
            $format = $matches['format'];
            $charset = $matches['charset'];
            if (strpos($str_attrs, 'AttributeInterpolation') !== false || strpos($str_attrs, 'AttributeList') !== false) {
                //todo
                throw new ReduceRuntimeException(' AttributeInterpolation or AttributeList');
            }

            $str_attrs_code = "<?php $str_attrs;";
            $stmts = self::getPHPParser()->parse($str_attrs_code);
            $codeFetcher = new NodeCodeFetcher($str_attrs_code);
            $attributes = array();
            $attributes_dyn = array();
            $nestedArray_in_classOrIdValue = array('id' => false, 'class' => false);

            foreach ($stmts[0]->items as $item) {
                $name = $item->value->items[0]->value;
                if ($name instanceof \PHPParser_Node_Scalar_String) {
                    $name = $name->value;
                } else {
                    throw new ReduceRuntimeException('att name should be string');
                }

                $value_node = $item->value->items[1]->value;
                $value_str = $codeFetcher->getNodeCode($value_node);

                if ('data' === $name) {
                    $value_str = '<?php ' . $value_str . ';';

                    $data_stmts = self::getPHPParser()->parse($value_str);
                    $data_codeFetcher = new NodeCodeFetcher($value_str);
                    self::returnDataAttributesByPHPParser($data_stmts[0], $data_codeFetcher, $attributes, $attributes_dyn);


                } else if ('class' === $name || 'id' === $name) {
                    self::pickEveryValueForClassId($value_node,$codeFetcher,$attributes,$nestedArray_in_classOrIdValue,$name);
                    if (!isset($attributes_dyn[$name]) || $attributes_dyn[$name] === false) {
                        $attributes_dyn[$name] = self::ifDynPHPNode($value_node);
                    }


                } else if ('TRUE' === strtoupper($value_str)) {
                    if ('html5' === $format) {
                        $attributes[$name] = true;
                    } else {
                        $attributes[$name] = $name;
                    }
                    $attributes_dyn[$name] = false;
                    //todo: null?
                } else if ('false' === strtolower($value_str) || 'null' === strtolower($value_str)) {
                    // do not output
                } else {
                    if (isset($attributes[$name])) {
                        // so that next assignment puts the attribute
                        // at the end for the array
                        unset($attributes[$name]);
                    }
                    $attributes[$name] = $value_str;
                    if (self::ifDynPHPNode($value_node)) {
                        $attributes_dyn[$name] = true;
                    } else {
                        $attributes_dyn[$name] = false;
                    }
                }
            }

            $multiple=array();
            foreach(array('class'=>' ','id'=>'-') as $item=>$sep){
                if (isset($attributes[$item])) {
                    if(count($attributes[$item])==1){
                        $multiple[$item]=false;
                    }else
                        $multiple[$item]=true;

                    $attributes[$item] = self::returnJoinedValueForClassId($attributes[$item], $sep, $attributes_dyn[$item], $nestedArray_in_classOrIdValue[$item],$multiple[$item]);
                }
            }

            $result = null;
            $result_dyn = array();
            foreach ($attributes as $name => $value) {
                if (null !== $result) {
                    $result .= ' ';
                }
                if ($value instanceof AttributeInterpolation) {
                    //todo
                    throw new ReduceRuntimeException('AttributeInterpolation ');
                    $result .= $value->value;
                } else if (true === $value) {
                    $result .=
                        htmlspecialchars($name, ENT_QUOTES, $charset);
                } else {
                    self::renderOneAttribute($attributes_dyn, $name, $value, $charset, $result, $result_dyn,$multiple);
                }
            }
            $result = ($result ? ' ' . trim($result) : '') .
                ($result_dyn ? '<?php ' . implode('', $result_dyn) . ' ?>' : '');
            $this->output = $oldOutput . $result;
            $this->lineno = $oldLineNo + substr_count($result, "\n");

        }
    }

    static protected function renderOneAttribute($attributes_dyn, $name, $value, $charset, &$result, &$result_dyn,$multiple)
    {
        if ($attributes_dyn[$name] === false) {
            $result .=
                htmlspecialchars($name, ENT_QUOTES, $charset) .
                '="' . htmlspecialchars(trim($value, "'"), ENT_QUOTES, $charset) . '"';
        } else {
            if (($name == 'class' || $name == 'id') && $multiple[$name]) {
                $check = 'if($__mthamlmore_attri_value!=="")';
            } else {
                $check = 'if(!is_null($__mthamlmore_attri_value))';
            }
            $name = htmlspecialchars($name, ENT_QUOTES, $charset);
            $result_dyn [] = <<<E
\$__mthamlmore_attri_value= $value;
$check echo ' $name="',htmlspecialchars(\$__mthamlmore_attri_value, ENT_QUOTES, '$charset'),'"' ;
E;
        }
    }

    // rule: if the probability of array is not 0, retrue true. so 'functionName(abc)' will return true, because we don't know what functionName returns.
    static protected function maybeArray($node)
    {
        if ($node instanceof \PHPParser_Node_Expr_Array) {
            foreach ($node->items as $item) {
                return self::maybeArray($item->value);
            }
        }
        // condition ? if : else
        if ($node instanceof \PHPParser_Node_Expr_Ternary) {
            if (!self::ifDynPHPNodes(array($node->if, $node->else))) {
                return false;
            }
        }
        // $abc . 'abc'
        if ($node instanceof \PHPParser_Node_Expr_Concat)
            return false;
        return self::ifDynPHPNode($node);
    }

    /*
     * https://github.com/nikic/PHP-Parser/blob/master/lib/PHPParser/BuilderAbstract.php normalizeValue
     */
    static protected function ifDynPHPNode($node)
    {
        return !($node instanceof \PHPParser_Node_Scalar_String ||
            $node instanceof \PHPParser_Node_Scalar_LNumber ||
            $node instanceof \PHPParser_Node_Scalar_DNumber ||
            $node instanceof \PHPParser_Node_Expr_ConstFetch);
    }

    static protected function ifDynPHPNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            if (self::ifDynPHPNode($node)) {
                return true;
            }
        }
    }

    static protected function returnDataAttributesByPHPParser($node, $codeFetcher, &$dest, &$dyn, $prefix = 'data')
    {
        foreach ($node->items as $item) {
            $value = $item->value;
            if ($value instanceof \PHPParser_Node_Expr_Array) {
                self::returnDataAttributesByPHPParser($value, $codeFetcher, $dest, $dyn, $prefix . '-' . $item->key->value);
            } else {
                $prefix_now = $prefix . '-' . ($item->key->value);
                if (!isset($dest[$prefix_now])) {
                    $dest[$prefix_now] = $codeFetcher->getNodeCode($value);
                    if (self::ifDynPHPNode($value)) {
                        $dyn[$prefix_now] = true;
                    } else
                        $dyn[$prefix_now] = false;
                }

            }
        }

    }

static  protected  function pickEveryValueForClassId($value_node,$codeFetcher,&$attributes,&$nested,$name)
{
    // for joined values
    if ($value_node instanceof \PHPParser_Node_Expr_Array) {

        // if array in array, like :  .add{:class => array($item['type'], $item == $sortcol ? array('sort', $sortdir):null) }

//                        if ($value_str[0] == '[' && substr($value_str, -1) == ']') {
//                            // ([$item['type'],3])
//                            $value_str = substr($value_str, 1, -1);
//                        } else if (substr($value_str, 0, 6) == 'array(' && substr($value_str, -1) == ')') {
//                            // (array($item['type'],3))
//                            $value_str = substr($value_str, 6, -1);
//                        }

        foreach($value_node->items as $item){
            self::pickEveryValueForClassId($item,$codeFetcher,$attributes,$nested,$name);
        }


    } else {
        $value_str= $codeFetcher->getNodeCode($value_node);
        if (isset($attributes[$name])) {
            $attributes[$name][] = $value_str;
        } else {
            $attributes[$name] = array($value_str);
        }
        if($nested[$name]===false)
            $nested[$name]= self::maybeArray($value_node);
    }

}
    static protected function returnJoinedValueForClassId(array $value, $separator, $dyn, $nest_array,$multiple)
    {
        if ($dyn === true) {
            /*
              %span.ok(class="widget_#{widget.number}")
                ->
              implode(' ',array('ok',('widget_' . (widget.number)))
            */
            // array_filter : filter non-null value
            // array flatten : iterator_to_array(new RecursiveIteratorIterator( new RecursiveArrayIterator($array)), FALSE);
            if ($nest_array) {
                return "implode('$separator',array_filter(" .
                'iterator_to_array(new RecursiveIteratorIterator( new RecursiveArrayIterator( ' .
                'array(' .
                implode(',', $value) .
                ') ' .
                ')), FALSE),' .
                'function($v){return is_null($v)?false:true;}))';
            } else {
                if($multiple){
                    return "implode('$separator',array_filter(" .
                    'array(' .
                    implode(',', $value) .
                    '),' .
                    'function($v){return is_null($v)?false:true;}))';
                }
                else
                    return $value[0];
            }
        } else {
            return implode($separator, $value);
        }
    }

    static protected function getPHPParser()
    {
        if (is_null(self::$php_parser)) {
            self::$php_parser = new \PHPParser_Parser(new LexerWithTokenOffsets);
        }
        return self::$php_parser;
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

class LexerWithTokenOffsets extends \PHPParser_Lexer
{
    public function getNextToken(&$value = null, &$startAttributes = null, &$endAttributes = null)
    {
        $tokenId = parent::getNextToken($value, $startAttributes, $endAttributes);
        $startAttributes['startOffset'] = $endAttributes['endOffset'] = $this->pos;
        return $tokenId;
    }
}

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

class ReduceRuntimeException extends MoreException
{
}
