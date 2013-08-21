MtHamlSnip
==========

Add Snip to MtHaml,  one way to “Don't Reinvent the Wheel".

Currently only support php, not Twig.

Demo
----

haml
```
@title{"one box"}
@box
    _
        this is title
    _
        thie is content
@title{"two columns"}
@two_columns
    _left hello,everyone
    _right
        @box
            _ title
            _ content
```

output
```
<h2>example 1 : one box</h2>
<div class="title">
  this is title
</div>
<div class="body">
  thie is content
</div>
<h2>example 2 : two columns</h2>
<div class="clear">
  <div class="left">
hello,everyone  </div>
  <div class="right">
    <div class="title">
title    </div>
    <div class="body">
content    </div>
  </div>
</div>
```

snips defined
```
$title=function($title){
    static $num=0;
    ++$num;
    return "%h2 example $num : $title";
};

$box=<<<S
.title
    @@@
.body
    @@@
S;

$two_columns=<<<S
.clear
    .left
        @@@left
    .right
        @@@right
S;
```

please see more examples at  examples/php.haml which is parsed by examples/php.php

Getting Started
-----

step 1: install MtHaml using composer.
the lastest version 1.2.2 of MtHaml has a little bug which affect MtHamlSnip, you should fix it manully or clone lastest MtHaml from github.

bug address: https://github.com/arnaud-lb/MtHaml/commit/eed2e29fd0214348d5ccac6cf55aab67d8a7bba6

step 2:  snip file mysnips.php ,defining one snip named box
```
<?php
$box=<<<S
.title
    @@@title
.body
    @@@body
S;
?>
```

step 2:  haml file callSnip.haml
```
@box
    _title an example
    _body
        %p content
```

step 3: php code
```
// ROOT_DIR is the root dir of MtHamlSnip
require_once ROOT_DIR . '/lib/MtHaml/Snip/entry.php';
$hamlfile=__DIR__ . '/php.haml';
$compiled = compilePhpSnipHaml(
    file_get_contents($hamlfile),
    array(
        'uses'=>array('mysnips.php'),
        'filename'=>$hamlfile,
        'enable_escaper' => false,
));
echo "<h1>rendered template:</h1>\n";
echo $compiled;
```

Glossary
----
* snip : a haml snippet which could be inserted into a haml string or another snip

* PlaceHolder : one type of Node, part of snip, which allow user to insert custom content.default values can be defined for a Placeholder.

* InlinePlaceholder :it's different with Placeholder, just like block element vs inline element in web DOM.
    * Warning: InlineSnipCaller is not a type of Node, it's parsed before parsing haml tree, see:
        MtHaml\Snip\Environment::parseInlinePlaceholder

* snip file : where snips live. snip file is parsed by the instance of Snip\SnipHouseInterface , which should be appointed at the first line of snip file
    * example: -# SnipParser="\MtHaml\Snip\Snip\SnipFileParser"
    * this is the default parser, you can ignore it.

* SnipCaller : a Node in a haml tree used to insert snip. "@box" is a SnipCaller used to insert snip "box".

* InlineSnipCaller : like InlinePlaceholder

* uses : a team of snip files used by a haml file or a snip file. snip files used by a haml is configed by option 'uses',
snip files by a snip file is configed by variable $__MtHamlSnip_uses when the snip file is parsed by the default parser "\MtHaml\Snip\Snip\SnipFileParser"

* mixes : a team of snip files mixed with a snip file.
they are configed by variable $__MtHamlSnip_mixes when the snip file is parsed by the default parser "\MtHaml\Snip\Snip\SnipFileParser"

Precautions
----

### Snip Attributes
1. Snip Attributes values can be supplied using SnipCaller Attributes with normal style or named argument style.
    for example there is a snip defined using closure
    ```
    $box = function($title,$body){
        return ".box\n  .title $title\n  .body $body";
    };
    ```
    you can supply attribute values using anyone of there ways:
    ```
    @box(my_title my_body)
    @box(title="my_title" body="my_body")
    @box(title="my_title" my_body)
    @box(body="my_body" my_title)
    ```

2. SnipCaller Attributes are parsed using Tag Attributes method, so SnipCaller Attributes syntax must observe Tag Attributes syntax.
    for example , it's illegal for html style
    ```
    @box("my title" "my body}
    ```
    you should use ruby style
    ```
    @box{"my title" "my body}


### snip file order when searching snip
if your set uses
```
'uses'=>array('1.php','2.php','3.php','1.php');
```
order of there uses is : 1.php > 3.php > 2.php,  snip file added later, priority level higher


But there is a file which be searched first of all, it's the haml file or the snip file where current parsed snip lives.

for example, a haml file shiped with some snips
```
@name
-#
  <?php
  $name='MtHamlSnip';
```
comppiled output is always 'MtHamlSnip' regardless of any snip files are supplied using 'uses'=>array().

example 2, a snip file
```
<?php
$__MtHamlSnip_uses=__DIR__.'\common1.php;' . __DIR__.'\common2.php;';
$title=<<<S
%h1
    @@@
S;
$welcometitle=function($name){
    return "@title welcome $name";
};
?>
```
if you call snip "welcomtitle"
```
@welcometitle(Jim)
```
Output always is
```
<h1>
welcome Jim</h1>
```
no matter there is snip named title in common1.php or common2.php.


### indent and newline about Text

sometimes you want to get output like
```
<h1>
  title
</h1>
```

but you get actually
```
<h1>
title</h1>
```

why? it's depends on your writing style:
```
%h1 title
```
is different with
```
%h1
    title
```

for more detail, you can see test/MtHaml/Snip/Tests/fixtures/environment/2.1.placeholder.test


one extra feature : prepare
-----
this is a feature whic has no relation with snip.

if you set options 'prepare'=>true , MtHamlSnip will first change code
```
{% $address='http://program-think.blogspot.com';$name='scil' %}
%div my name is {= $name =}, i love this IT blog {= $address =} which is blocked by GFW
```
to
```
<?php $address='http://program-think.blogspot.com';$name='scil' ; ?>
%div my name is <?php echo $name ; ?>, i love this IT blog <?php echo $address ; ?> which is blocked by GFW
```
then, to
```
%div my name is scil, i love this IT blog http://program-think.blogspot.com which is blocked by GFW
```
this is normal haml code,which will be compiled to
```
<div>my name is scil, i love this it blog http://program-think.blogspot.com which is blocked by GFW</div>

```

notice: {% .. %} must monopolize one line, because regular expression uses '^' and '$'.

code: MtHaml\Snip\Environment::prepare


Development Rule
-----

1. no change to MtHaml

2. place some variables at Enviroment::options. Maybe better at Root of Tree,but Rule 1 would be destroyed.
    1. code: MtHaml\Snip\Environment::construct
    2. haml file name also be placed in Enviroment::options, so it's easy to track the process of calling snip, see MtHaml\Snip\NodeVisitor\PhpRenderer::options['filename']
    3. how to access Environment object?
        1. use $this->env in Parser ( add attribute $env )
        2. use $this->env in Renderer ( availabel at MtHaml)
        3. SnipCaller::getEnv(). Environment obj is attached with SnipCaller to make sure snip parsed using right use files.
            see: test/MtHaml/Snip/Tests/fixtures/environment/4.3.use snip from other snip file 2.test
        4. no formal way to access Environment in Visitor except Renderer, you can use $GLOBAL to access an Environment object when debugging.

Development memorandum
--------

1. Snip called by SnipCaller is invoked at render stage,not parse stage, so Illegal nesting can not be checked.  code : MtHaml\Snip\NodeVisitor\PhpRenderer::enterSnipCaller

2. Snp called by InlineSnipCaller is invokded before parse stage.  code:
    1. MtHaml\Snip\Environment::parseInlineSnipCaller  (InlineSnipCaller is not parsed to an instance of Node for simplify)

2. SnipCaller/InlineSnipCaller can call snip located same file, because current file is added to snipfiles array. code:
    MtHaml\Snip\Snip\SnipHouse::getSnipAndFiles

4. the only use of Log is to record the process of calling snip. code:
    1. MtHaml\Snip\Environment::__construct  $options['log'] ; $options['debug'](enable log)
    2. MtHaml\Snip\NodeVisitor\PhpRenderer::enterSnipCaller

5. how indent works well? code:
    1. MtHaml\Snip\Environment::__construct  $options['baseIndent']
    2. MtHaml\Snip\NodeVisitor\PhpRenderer::__construct
