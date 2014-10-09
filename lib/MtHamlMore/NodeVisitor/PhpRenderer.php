<?php

namespace MtHamlMore\NodeVisitor;

use MtHaml\Environment;
use MtHaml\Exception;
use MtHamlMore\Node\HtmlTag;
use MtHamlMore\Node\SnipCaller;
use MtHamlMore\Node\PlaceholderValue;
use MtHamlMore\Node\Placeholder;
use MtHamlMore\Lib\NodeCodeFetcher;
use MtHamlMore\Lib\LexerWithTokenOffsets;
use MtHaml\Node\Tag;
use MtHaml\Runtime\AttributeInterpolation;


class PhpRenderer extends \MtHaml\NodeVisitor\PhpRenderer implements VisitorInterface
{
    static private $php_parser;
    private $reduceRuntimeArrayTolerant = false;

    public function __construct(Environment $env)
    {
        parent::__construct($env);
        if ($env->reduceRuntimeArrayTolerant )
            $this->reduceRuntimeArrayTolerant = true;
    }

    /*
    %input(selected)
        rendered by MtHaml:
             <?php echo MtHaml\Runtime::renderAttributes(array(array('selected', TRUE)), 'html5', 'UTF-8'); ?>
    %input(selected=true)
        rendered by MtHaml:
             <?php echo MtHaml\Runtime::renderAttributes(array(array('selected', true)), 'html5', 'UTF-8'); ?>

    %a(title="title" href=href) Stuff
        rendered by MtHaml:
            <?php echo MtHaml\Runtime::renderAttributes(array(array('title', 'title'), array('href', href)), 'html5', 'UTF-8'); ?>
        attributes_dyn: title is not dyn, but href is

    %script{:type => "text/javascript", :src => "javascripts/script_#{2 + 7}"}
        rendered by MtHaml:
            <?php echo MtHaml\Runtime::renderAttributes(array(array('type', 'text/javascript'), array('src', ('javascripts/script_' . (2 + 7)))), 'html5', 'UTF-8'); ?>
        attributes_dyn: type is not dyn; but src is

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



    ### data ###

    %a{:href=>"/posts", :data => ['author_id' => 123]} Posts By Author
        rendered by MtHaml:
            <a <?php echo MtHaml\Runtime::renderAttributes(array(array('href', '/posts'), array('data', (['author_id' => 123]))), 'html5', 'UTF-8'); ?>>Posts By Author</a>

    %a{:href=>"/posts", :data => ['author_id' => $data_id,'ok' => 3, 'no'=>$data_id+1]}
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
        // <attrs> like: array(array('title',(title)),array('href',href),array('id',id))
        $re = '@
\ <\?php\ echo\ MtHaml\\\\Runtime::renderAttributes\(
(?<attrs>array\(array\(.+\)\))
,\ \'
(?<format>\w+)
\',\ \'
(?<charset>[-\w]+)\'
(?:,\ (?<escape>true|false)
 )?
\);\ \?>
@xA';

        if (preg_match($re, $newOutput, $matches)) {
            $str_attrs = $matches['attrs'];
            $format = $matches['format'];
            $charset = $matches['charset'];
            $escape=isset($matches['escape'])? ($matches['escape']=='true'?true:false) : true;
            if (strpos($str_attrs, 'AttributeInterpolation') !== false || strpos($str_attrs, 'AttributeList') !== false) {
                //todo
                throw new ReduceRuntimeException(' AttributeInterpolation or AttributeList');
            }

            $str_attrs_code = "<?php $str_attrs;";
            $stmts = self::getPHPParser()->parse($str_attrs_code);
            $codeFetcher = new NodeCodeFetcher($str_attrs_code);
            $attributes = array();
            $attributes_dyn = array();
            // :class=>$myClass,:href=>$url  是动态的，但只是单个变量，有利于产生简洁代码
            $attributes_singleVar = array();
            // is id or class in array form?
            // like: array('id', ([$item['type'], $item['number']]))  ([..])内的元素组合而成id
            $array_in_classOrIdValue = array('id' => false, 'class' => false);

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
                    self::parseDataAttributes($value_node, $codeFetcher, $attributes, $attributes_dyn, $attributes_singleVar);
                } else if ('class' === $name || 'id' === $name) {
                    $this->pickEveryValueForClassId($value_node, $codeFetcher, $attributes, $array_in_classOrIdValue, $name);
                    if (!isset($attributes_dyn[$name]) || $attributes_dyn[$name] === false) {
                        $attributes_dyn[$name] = self::ifDynNode($value_node);
                    }
                    $attributes_singleVar[$name] = $value_node;


                } else if ('TRUE' === strtoupper($value_str)) {
                    if ('html5' === $format) {
                        $attributes[$name] = true;
                    } else {
                        $attributes[$name] = $name;
                    }
                    $attributes_dyn[$name] = false;
                    //todo: when null?
                } else if (in_array(strtolower($value_str), array('false', 'null'), true)) {
                    // do not output
                } else {
                    if (isset($attributes[$name])) {
                        // so that next assignment puts the attribute
                        // at the end for the array
                        unset($attributes[$name]);
                    }
                    $attributes[$name] = $value_str;
                    if (self::ifDynNode($value_node)) {
                        $attributes_dyn[$name] = true;
                        if ($value_node instanceof \PHPParser_Node_Expr_Variable)
                            $attributes_singleVar[$name] = true;
                        else
                            $attributes_singleVar[$name] = false;
                    } else {
                        $attributes_dyn[$name] = false;
                    }
                }
            }

            foreach (array('class' , 'id' ) as $name ) {
                if (isset($attributes[$name])) {
                    if (count($attributes[$name]) == 1) {
                        $singleItemForClassId = true;
                        if ($attributes_singleVar[$name] instanceof \PHPParser_Node_Expr_Variable)
                            $attributes_singleVar[$name] = true;
                        else
                            $attributes_singleVar[$name] = false;
                    } else {
                        $attributes_singleVar[$name] = false;
                        $singleItemForClassId = false;
                    }
                    unset($singleItemForClassId);
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
                        $escape ? htmlspecialchars($name, ENT_QUOTES, $charset) : $name;
                } else {
                    self::renderOneAttribute($name, $value,$escape, $attributes_dyn[$name], !empty($attributes_singleVar[$name]), empty($array_in_classOrIdValue[$name]), $charset, $result, $result_dyn);
                }
            }
            $result = ($result ? ' ' . trim($result) : '') .
                ($result_dyn ? '<?php ' . implode('', $result_dyn) . ' ?>' : '');
            $this->output = $oldOutput . $result;
            $this->lineno = $oldLineNo + substr_count($result, "\n");

        }
    }

    static protected function renderOneAttribute($name, $value,$escape, $dyn, $singleVar, $array_no_exists, $charset, &$result, &$result_dyn)
    {
        if ($dyn === false) {
            if(is_array($value)){ // for class or id
                $value= implode($name=='class'?' ':'-',$value);
            }
            if ($escape)
            $result .=
                htmlspecialchars($name, ENT_QUOTES, $charset) .
                '="' . htmlspecialchars(trim($value, "'"), ENT_QUOTES, $charset) . '"';
            else
                $result .=
                    $name.
                    '="' . trim($value, "'") . '"';
        } else {
            $name = $escape ? htmlspecialchars($name, ENT_QUOTES, $charset) : $name;

            if(is_array($value)){
                $value=  'array(' . implode(',', $value) . ') ';
            }
            $s=$singleVar?'true':'false';
            $a=$array_no_exists?'true':'false';
            $e=$escape?$charset:false;
            $result_dyn [] =  "\MtHamlMoreRuntime\Runtime::renderAttribute('$name',$value,$s,$a,'$e');";
            return;

            if ($singleVar && $array_no_exists) {
                $namevalue= $escape ? "htmlspecialchars($value, ENT_QUOTES, '$charset')" :$value ;
                $result_dyn [] = <<<E
if(!is_null($value)) echo ' $name="',$namevalue,'"' ;
E;
            } else {
                if (($name == 'class' || $name == 'id') && substr($value, 0, 9) == "implode('") {
                    $check = 'if($__mthamlmore_attri_value!=="")';
                } else {
                    $check = 'if(!is_null($__mthamlmore_attri_value))';
                }
                $namevalue= $escape ?  "htmlspecialchars(\$__mthamlmore_attri_value, ENT_QUOTES, '$charset')":'$__mthamlmore_attri_value' ;
                $result_dyn [] = <<<E
\$__mthamlmore_attri_value= $value;
$check echo ' $name="',$namevalue,'"' ;
E;
            }
        }
    }

    static protected function parseDataAttributes($node, $codeFetcher, &$dest, &$dyn, &$singleValue, $prefix = 'data')
    {
        foreach ($node->items as $item) {
            $value = $item->value;
            if ($value instanceof \PHPParser_Node_Expr_Array) {
                self::parseDataAttributes($value, $codeFetcher, $dest, $dyn, $singleValue, $prefix . '-' . $item->key->value);
            } else {
                $prefix_now = $prefix . '-' . ($item->key->value);
                if (!isset($dest[$prefix_now])) {
                    $dest[$prefix_now] = $codeFetcher->getNodeCode($value);
                    if (self::ifDynNode($value)) {
                        $dyn[$prefix_now] = true;
                        if ($value instanceof \PHPParser_Node_Expr_Variable)
                            $singleValue[$prefix_now] = true;
                        else
                            $singleValue[$prefix_now] = false;
                    } else
                        $dyn[$prefix_now] = false;
                }

            }
        }

    }

    protected function pickEveryValueForClassId($value_node, $codeFetcher, &$attributes, &$array, $name)
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

            foreach ($value_node->items as $item) {
                // why $item->value? because $item is instance of PHPParser_Node_Expr_ArrayItem
                self::pickEveryValueForClassId($item->value, $codeFetcher, $attributes, $array, $name);
            }


        } else {
            $value_str = $codeFetcher->getNodeCode($value_node);
            if (isset($attributes[$name])) {
                $attributes[$name][] = $value_str;
            } else {
                $attributes[$name] = array($value_str);
            }
            if ($array[$name] === false)
                $array[$name] = self::maybeArrayReturnedNode($value_node, $this->reduceRuntimeArrayTolerant);
        }

    }

    /*
     *  if node will be considered to return array
    * rule: if the probability of array is not 0, retrue true. so 'functionName(abc)' will return true, because we don't know what functionName returns.
     * dev:
        no need to consider Array type
        because  $this->pickEveryValueForClassId  has go into it and split into elements.
        if ($node instanceof \PHPParser_Node_Expr_Array) { }
     */
    static protected function maybeArrayReturnedNode($node, $tolerant)
    {
        if ($tolerant) {
            if ($node instanceof \PHPParser_Node_Expr_Ternary) {

                if (($node->if instanceof \PHPParser_Node_Expr_Array) || ($node->else instanceof \PHPParser_Node_Expr_Array) )
                    return true;
            }
//            if (($node instanceof \PHPParser_Node_Expr_Variable) ||
//                ($node instanceof \PHPParser_Node_Expr_FuncCall)
//            )
//                return false;
            return false;
        } else {
            // condition ? if : else
            if ($node instanceof \PHPParser_Node_Expr_Ternary) {
                if (!self::ifDynNodes(array($node->if, $node->else))) {
                    return false;
                }
            } // $abc . 'abc'
            else if ($node instanceof \PHPParser_Node_Expr_Concat)
                return false;

            return self::ifDynNode($node);
        }

    }


    /*
     * https://github.com/nikic/PHP-Parser/blob/master/lib/PHPParser/BuilderAbstract.php normalizeValue
     */
    static protected function ifDynNode($node)
    {
        return !($node instanceof \PHPParser_Node_Scalar_String ||
            $node instanceof \PHPParser_Node_Scalar_LNumber ||
            $node instanceof \PHPParser_Node_Scalar_DNumber ||
            $node instanceof \PHPParser_Node_Expr_ConstFetch);
    }

    static protected function ifDynNodes(array $nodes)
    {
        foreach ($nodes as $node) {
            if (self::ifDynNode($node)) {
                return true;
            }
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

