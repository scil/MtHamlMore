<?php

namespace MtHaml\More\NodeVisitor;

use MtHaml\Exception;
use MtHaml\More\Exception\MoreException;
use MtHaml\More\Node\HtmlTag;
use MtHaml\More\Node\SnipCaller;
use MtHaml\More\Node\PlaceholderValue;
use MtHaml\More\Node\Placeholder;
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
        if($this->env->noReduceRuntime){
            parent::renderDynamicAttributes($tag);
            return;
        }
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
            if (strpos($str_attrs, 'AttributeInterpolation') !==false || strpos($str_attrs, 'AttributeList') !==false ) {
                //todo
                throw new MoreException('reduce_runtime: AttributeInterpolation or AttributeList');
                return;
            }else{
                $str_attrs = explode('), array(', $str_attrs);
                $attributes = array();
                $attributes_dyn = array();
                $attributes_nest_array = array();
                foreach ($str_attrs as $str_attr) {
                    // 3td arg '2' is necessary when for joined values like "%div{:class => [$item['type'], $item['urgency']]}"
                    list ($name, $value) = explode(', ', $str_attr,2);
                    $name=trim($name,"'");

                    $attributes_nest_array[$name]=false;
                    if ((substr($value,0,1))=="'"){
                        if( !isset($attributes_dyn[$name]) || $attributes_dyn[$name]==false){
                            $attributes_dyn[$name]=false;
                        }
                    }
                    else{
                        $attributes_dyn[$name]=true;
                        if(strpos($value,'array(')!==false){
                            $attributes_nest_array[$name]=true;
                        }
                    }

                    if ('data' === $name) {
                        // change "(['id'=>2])" to "array('id'=>2) "
                        if(substr($value,0,2)=='(['){
                            $value_inner= substr($value,2,strlen($value)-4) ;
                        }else {
                            $value_inner= substr($value,0,strlen($value)-9) ;
                        }
                        $value = 'array('. $value_inner.')';

                        // no nest array
                        if (strpos($value_inner,'array(')===false && strpos($value_inner,'[')===false){
                            // 'ok'=>3,'no'=>4
                            if(preg_match_all("/,\\s*'([^']+)'\\s*=>\\s*(.+?)(?=,\\s*'|\\s*$)/" , ','.$value_inner,$matches)){
                                foreach($matches[1] as $index=>$key){
                                    $attributes['data-'.$key]=$matches[2][$index];
                                    // for simple , every data is dyn
                                    $attributes_dyn['data-'.$key]=true;
                                }
                            }
                        }
                        else{

                            set_error_handler(function(){throw new \Exception('eval data value wrong');});
                            try{
                                eval("\$dataValue=$value;");
                                self::returnDataAttributes($attributes, $dataValue);
                            }
                            catch(\Exception $e){
                                restore_error_handler();
                                //todo
                                throw new MoreException('reduce_runtime: nested data only accept non-dyn value');
                                return;

                            }
                            restore_error_handler();

                        }

                    } else if ('class' === $name || 'id' === $name) {
                        // for joined values
                        if(substr($value,0,2)=='([' && substr($value,-2)=='])'){
                            // ([$item['type'],3])
                            $value =  substr($value,2,strlen($value)-4);
                        }else if (substr($value,0,7)=='(array(' && substr($value,-2)=='))'){
                            // (array($item['type'],3))
                           $value=substr($value,7,strlen($value)-9);
                        }
                        if (isset($attributes[$name])) {
                                $attributes[$name][]=  $value;
                        } else {
                            $attributes[$name] = array($value);
                        }
                    } else if ('TRUE' === strtoupper($value)) {
                        if ('html5' === $format) {
                            $attributes[$name] = true;
                        } else {
                            $attributes[$name] = $name;
                        }
                    //todo: null?
                    } else if ('false' === strtolower($value) || null === $value) {
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

                if(isset($attributes['class'])){
                    $attributes['class'] = self::returnJoinedValue($attributes['class'],' ',$attributes_dyn['class'],$attributes_nest_array['class']);
                }
                if(isset($attributes['id'])){
                    $attributes['id'] = self::returnJoinedValue($attributes['id'],'-',$attributes_dyn['id'], $attributes_nest_array['id']);
                }

                $result = null;
                $result_dyn=array();
                foreach ($attributes as $name => $value) {

                    if (null !== $result) {
                        $result .= ' ';
                    }
                    if ($value instanceof AttributeInterpolation) {
                        //todo
                        throw new MoreException('reduce_runtime: AttributeInterpolation ');
                        $result .= $value->value;
                    } else if (true === $value) {
                        $result .=
                            htmlspecialchars($name, ENT_QUOTES, $charset);
                    } else {
                        self::renderOneAttribute($attributes_dyn,$name,$value,$charset,$result,$result_dyn);
                    }
                }
                $result= ($result?' '.trim($result):'') .
                    ($result_dyn? '<?php '. implode('',$result_dyn).' ?>' :'' );
                $this->output = $oldOutput . $result;
                $this->lineno = $oldLineNo + substr_count($result, "\n");

            }

        }

    }
    static protected function renderOneAttribute($attributes_dyn,$name,$value,$charset,&$result,&$result_dyn)
    {
        // empty:  false or unset(like ['data-author_id'=>3] )
        if(empty($attributes_dyn[$name])){
            $result .=
                htmlspecialchars($name, ENT_QUOTES, $charset).
                '="'.htmlspecialchars(trim($value,"'"), ENT_QUOTES, $charset).'"';
        }
        else{
            if($name=='class' || $name=='id'){
                $check='if($__mthamlmore_attri_value!=="")';
            }else{
                $check='if(!is_null($__mthamlmore_attri_value))';
            }
            $name = htmlspecialchars($name, ENT_QUOTES, $charset);
            $result_dyn []= <<<E
\$__mthamlmore_attri_value= htmlspecialchars($value, ENT_QUOTES, '$charset');
$check echo ' $name="'.\$__mthamlmore_attri_value.'"' ;
E;
        }
    }
    static private function returnDataAttributes(&$dest, $value, $prefix = 'data')
    {
        if (\is_array($value) || $value instanceof \Traversable) {
            foreach ($value as $subname => $subvalue) {
                self::returnDataAttributes($dest, $subvalue, $prefix.'-'.$subname);
            }
        } else {
            if (!isset($dest[$prefix])) {
                $dest[$prefix] = $value;
            }
        }
    }
    static protected function returnJoinedValue(array $value,$separator,$dyn, $nest_array)
    {
        if($dyn===true){
            /*
              %span.ok(class="widget_#{widget.number}")
                ->
              implode(' ',array('ok',('widget_' . (widget.number)))
            */
            // array_filter : filter non-null value
            // array flatten : iterator_to_array(new RecursiveIteratorIterator( new RecursiveArrayIterator($array)), FALSE);
            if($nest_array){
                return "implode('$separator',array_filter(".
                    'iterator_to_array(new RecursiveIteratorIterator( new RecursiveArrayIterator( '.
                            'array('.
                                implode(',',$value) .
                            ') '.
                        ')), FALSE),'.
                    'function($v){return is_null($v)?false:true;}))';
            }
            else{
               return "implode('$separator',array_filter(".
                   'array(' .
                        implode(',',$value).
                    '),'.
                   'function($v){return is_null($v)?false:true;}))';
            }
        }else{
            return implode($separator,$value);
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

