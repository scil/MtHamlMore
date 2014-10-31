MtHamlMore
==========

Add some features like snippet to MtHaml,  main purpose is “Don't Reinvent the Wheel".

Both php and Twig are supported.

Install method: add ``` "scil/mthaml-more": "*" ``` to composer.json, see details at docs.

Main Feature :snip
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

write haml like so:
```
@box
    _ this is title
    _ this is content
```

output html :
```
<div class="title">
  this is title
</div>
<div class="body">
  this is content
</div>
```

'@@@' is a placeholder where you can put your own content,and you could define default value for it, or even set global placeholder default value use option  'globalDefaultPlaceholderValue', that's useful if you want all placeholder rendered to empty string when you forget apply placeholder value.


second example: inline placeholder
snips:
```
  $title="%h1 welcome to {@@}.";
  $title2="%h1 this is a placeholder with default value. welcome to {@:MtHamlMore(default)@}.";
  $title3="%h1 this is a named placeholder.welcome to {@name@}.";
  $title4="%h1 welcome to {@name:MtHamlMore(default)@}.";
  $div='.{@@}';
```
haml:
```
@title Moon
@title2 Moon
@title2
@title3
  _name Moon
@title4
  _name Sun
@title4
@div box
```
output:
```
@title Moon
@title2 Moon
@title2
@title3
  _name Moon
@title4
  _name Sun
@title4
@div box
```

third example
```
@grid(grid="4 -4 4" fluid="1")
  _ %h1 4
  _ 4 offset 4
```
This is calling a snip named grid, and two arguments. Usually, I use snip @grid to define grid layout.
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

Attribute values can be writed as named placeholdervalue, that means:
```
@call(attri="hello")
```
is equal with
```
@call
  _attri hello
```
Please note, this form is not treated as attribute value
```
@call
  _attri
    hello
```
And
```
@call
  _attri |
    hello |
    haml |
```
equals with
```
@call(attri="hello haml ")
```

see more examples at : "docs/0. Snip Examples.md"



Extra Feature 1 : HtmlTag
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



Extra Feature 2 : reduce runtime
-----
Sometimes there are some 'MtHaml\Runtime' in php files produced by MtHaml, if you dislike it ,you may try
```
$compiled = compilePhpMoreHaml(
    file_get_contents($hamlfile),
    array( ),
    array(
        'filename'=>$hamlfile,
        'reduce_runtime' => true,
));
```
'reduce_runtime'=>true could reduce the appearance of 'MtHaml\Runtime',or replace it with \MtHamlMoreRuntime\Runtime::renderAttribute which is much simpler.

It's not perfect,but works in normal situation.

Works well for these haml:
```
%input(selected)

%input(selected=true)

%a(title="title" href=$href) Stuff

%script{:type => "text/javascript", :src => "javascripts/script_#{2 + 7}"}

%span.ok(class="widget_#{$widget['number']}")

.item{:class => $item['is_empty'] ? "empty":null}

%div.add{:class => [$item['type'], $item == $sortcol ? ['sort', $sortdir]:null] } Contents

#div{:class => array($item['type'], $item['urgency']), :id => array($item['type'], $item['number']>3?'big' :'small') }

%a{:data => array('author_id' => $data_id,'abc'=>array('ok'=>3,'no'=>$data_id+1))}


```

Not works

1. there is 'AttributeInterpolation' or 'AttributeList' in php files produced by MtHaml. I have not encounter this so far.

2. (welcome add your find)



This feature supported only for php ,not Twig.

code:
* MtHamlMore\NodeVisitor\PhpRenderer::renderDynamicAttributes
* MtHamlMoreRuntime\Runtime::renderAttribute



### option 'reduce_runtime_array_tolerant'
The :class and :id attributes can be specified as a Ruby array, like
```
#div{:class => array($position,$item2['type'], $item2['urgency']), :id => array($item2['type'], $item2['number']>3?'big' :'small') }
```
if no one of $position, $item2['type'], $item2['urgency'] or $item2['type'] is an array, you could add
``` 'reduce_runtime_array_tolerant'=>true,``` to 3rd argument of compilePhpMoreHaml.
Then array flatten is not needed in this case.

code: MtHamlMore\NodeVisitor\PhpRenderer::returnJoinedValueForClassId


when option 'reduce_runtime_array_tolerant' is true , only these situations will use array flatten right now:

* if or else is an array in 'condition?if:else',like
```
%div.add{:class => array($item['type'], $item == $sortcol ? array('sort', $sortdir):null) } Contents
```
* (add your needs in : MtHamlMore\NodeVisitor\PhpRenderer::maybeArrayReturnedNode)




Extra Feature 3 : prepare
-----

if you set options 'prepare'=>true , MtHamlMore will first execute php code defined by {% %} and {= =}.

like this:
```
{% $address='http://program-think.blogspot.com';$name='scil' %}
%div my name is {= $name =}, i love this IT blog {= $address =} which is blocked by GFW
```

executed to
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


