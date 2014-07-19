MtHamlMore
==========

Add some features like snippet to MtHaml,  main purpose is “Don't Reinvent the Wheel".

Currently only php is supported , no Twig.

main feature :snip
----
if there is a box structure which is used many times, you can define it as a snip,for example
```
$box=<<<S
.title
    @@@
.body
    @@@
S;
```

then in your haml file write
```
@box
    _ this is title
    _ this is content
```

output html will is
```
<div class="title">
  this is title
</div>
<div class="body">
  this is content
</div>
```

'@@@' is placeholder where you can put your own content,and you could define default values for it.


second example
```
@grid(grid="4 -4 4" fluid="1")
  _ %h1 4
  _ 4 offset 4
```
This is calling a snip named grid, and two arguments. Usually, i use snip @grid to define grid layout.
'fluid="1"' is fluid layout, 'grid="4 -4 4" is one type of 12 columns grid.
What this statement output depends on how your snip writes.
In 'examples/snips/php.php', there is an snip which defines Twitter Bootstrop v2 grid. In case of this,output would be
```
<div class="row-fluid show-grid">
    <div class="span4">
      <h1>4</h1>
    </div>
    <div class="span4 offset4">
      4 offset 4
    </div>
</div>
```

see more examples at : "docs/0. Snip Examples.md"

extra feature 1 : HtmlTag
-----
html tags can be used like haml tag ,not only
```
%div
  <p> hello </p>
```
which is supported by MtHaml, but also
```
<div>
    %p hello
</div>
```
This feature enables you to copy any html code into a haml file, only make sure code apply haml indent syntax.

code: MtHamlMore\Parser::parseHtmlTag



extra feature 2 : reduce runtime
-----
Sometimes there are some 'MtHaml\Runtime' in php files produced by MtHaml, if you dislike it and accept ugly php files,you may try
```
$compiled = compilePhpMoreHaml(
    file_get_contents($hamlfile),
    array( ),
    array(
        'filename'=>$hamlfile,
        'reduce_runtime' => true,
));
```
'reduce_runtime'=>true could reduce the appearance of 'MtHaml\Runtime'.

It's not perfect,but works in normal situation.

Works well for these haml:
```
%input(selected)
%input(selected=true)
%a(title="title" href=$href) Stuff
%script{:type => "text/javascript", :src => "javascripts/script_#{2 + 7}"}
%span.ok(class="widget_#{$widget['number']}")
.item{:class => $item['is_empty'] ? "empty":null}
%div.add{:class => array($item['type'], $item == $sortcol ? array('sort', $sortdir):null) } Contents
#div{:class => array($item['type'], $item['urgency']), :id => array($item['type'], $item['number']>3?'big' :'small') }
%a{:data => array('author_id' => $data_id,'abc'=>array('ok'=>3,'no'=>$data_id+1))}
```

Not works

1. there is 'AttributeInterpolation' or 'AttributeList' in php files produced by MtHaml. I have not encounter this so far.

2. (welcome add your find)



code: MtHamlMore\NodeVisitor\PhpRenderer::renderDynamicAttributes


### option 'reduce_runtime_array_tolerant'
The :class and :id attributes can be specified as a Ruby array, like
```
#div{:class => array($position,$item2['type'], $item2['urgency']), :id => array($item2['type'], $item2['number']>3?'big' :'small') }
```
if no one of $position, $item2['type'], $item2['urgency'] or $item2['type'] is an array, you could add
``` 'reduce_runtime_array_tolerant'=>true,``` to 3rd argument of compilePhpMoreHaml.
It will produce less urgly php code because array flatten is not needed in this case.

code: MtHamlMore\NodeVisitor\PhpRenderer::returnJoinedValueForClassId


when option 'reduce_runtime_array_tolerant' is true , only these situations will use array flatten right now:

1. if or else is an array in 'condition?if:else',like
```
%div.add{:class => array($item['type'], $item == $sortcol ? array('sort', $sortdir):null) } Contents
```
2. (add your needs in : MtHamlMore\NodeVisitor\PhpRenderer::maybeArrayReturnedNode)


extra feature 3 : prepare
-----

if you set options 'prepare'=>true , MtHamlMore will first change code
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

code: MtHamlMore\Environment::prepare

