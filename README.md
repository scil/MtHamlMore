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
```
This is calling a snip named grid, and two arguments. Usually, i use snip @grid to define grid layout.
'fluid="1"' is fluid layout, 'grid="4 -4 4" is one type of 12 columns grid.
What this statement output depends on how your snip writes.
In 'examples/snips/php.php', there is an snip which defines Twitter Bootstrop v2 grid.

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

code: '<div>' is parse as HtmlTag, see MtHaml\More\Parser::parseHtmlTag



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
%div.add{:class => [$item['type'], $item == $sortcol ? array('sort', $sortdir):null] } Contents
%div{:class => [$item['type'], $item['urgency']], :id => [$item['type'], $item['number']] }
#div{:class => [$item['type'], $item['urgency']], :id => [$item['type'], $item['number']>3?'big' :'small'] }
%a{:href=>"/posts", :data => ['author_id' => 'data_id','abc'=>3,'no'=>strlen('abc')]} Posts By Author
```

Not works

1. nested array for data attribute value, i've tried token or re to parse nested array string and finally give up. ```  %a{:data => ['author_id' => $data_id,'abc'=>array('ok'=>3,'no'=>$data_id+1)]} ```

2. 'AttributeInterpolation' or 'AttributeList' is produced by MtHaml.

3. (welcome add your find)



node: MtHaml\More\NodeVisitor\PhpRenderer::renderDynamicAttributes


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

code: MtHaml\More\Environment::prepare

