-# SnipParser="\MtHaml\More\Snip\SnipFileParser"
<?php
// special vars, see README.md::Glossary uses && mixes
$__MtHamlMore_uses=__DIR__.'\abstract.php';
$__MtHamlMore_mixes=__DIR__.'\common.php';

$css=function($url){
  return  "%link(href=\"$url\" rel=\"stylesheet\"  )";
};

$title=function($title){
    static $num=0;
    ++$num;
    return <<<S
%h2(style="font-size:1.5em;margin-top:20px;border:4px solid grey;padding:10px") example $num : $title
S;
};

$snipInSnipTitle=function($title=''){
  static $num=0;
  ++$num;
if($title){
  return <<<S
@title{"snip in snip $num: $title"}
S;
}else{
    return <<<S
@title{"snip in snip $num"}
S;
}
};

$more='more...';

$footer=<<<S
#footer.mid
    %p footer
    %hr
    %p Powered by MtHaml and MtHamlSnip
S;

$box=<<<S
.title
    @@@
.body
    @@@
S;

$namedBox=<<<S
.title
    @@@title
.body
    @@@body
S;

$mixed=<<<S
.title
  @@@title1
.body
  %p
    @@@
.title
  @@@title2
.body
  %p
    @@@
  %p
    @@@
  %div
    @@@
S;

$default=<<<S
.title
  @@@
    snip title
.body
  @@@ body
S;

$closure=function(){
    return ".body\n  @@@";
};
$person=function($name='scil',$age=21){
    return <<<S
.person
    .name $name
    .age $age
S;
};


$nested=<<<S
@person(age="33")
S;


$nested2=<<<S
@box
    _ TITLE defined by caller snip
    _
        @@@
S;


$mygrid=<<<S
@fluidGrid(grid="2 4 6")
    _
        @@@
    _
        @@@
    _
        @@@
S;

$fluidGrid=function($grid){
    $lines=array("@grid(fluid=\"1\" grid=\"$grid\")");
    foreach (explode(' ',$grid) as $v) {
        //if offset
        if($v[0]==='-') {
            continue;
        }
        $lines[] ="\n  _\n    @@@";
    }
    $haml = implode('', $lines);
    return $haml;
};

/*
 * example: (fluid="1" grid="4 -4 4")
 * url:http://twitter.github.io/bootstrap/2.3.2/scaffolding.html#gridSystem
%link(href="http://getbootstrap.com/2.3.2/assets/css/bootstrap.css" rel="stylesheet"  )
 */
$grid=function($grid,$fluid=0)
{
    $gridclass= $fluid? 'row-fluid':'row';
    $lines=array(".$gridclass.show-grid");
    $offset=false;
    foreach (explode(' ',$grid) as $v) {
        if($v[0]==='-') {
            $offset=$v[1];
            continue;
        }
        if($offset){
            $lines[]="\n  .span$v.offset$offset\n    @@@";
            $offset=false;
        }else{
            $lines[]="\n  .span$v\n    @@@";
        }
    }
    return implode('', $lines);
};


$abstract="@abstract_box\n  @@@";
$common="@common_box";

$inline="hello,{@@}";
$inlineS="hello,\\{@@}";
$inlineDefaultValue="hello,{@:scil@}";
$inlineDefaultValueS="hello,\\{@:scil@}";
$inlineName="hello,{@name@}";
$inlineNameS="hello,\\{@name@}";
$inlineNameDefaultValue="hello,{@name:scil@}";
$inlineNameDefaultValueS="hello,\\{@name:scil@}";

/*
@hello(n="3" ok="31-#{3+3}" p=6+4 pp="#{2+1}")
*/
?>
