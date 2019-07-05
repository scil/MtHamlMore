原理
===
step1: 用parser解析成树，父节点的儿子们存储在父节点的childs属性上

step2: visitor访问树，做检查、修改之类

step3: renderer来渲染树 (逻辑上讲，renderer也是一种visitor，不过是个特殊的visitor)


渲染的理解
====

从文本入手：两种方式提供的文本
---
例1 行内  
`.div ok`    // 此时.div 的content是：InterpolatedString； render时，没有换行效果

例2 块上  
```
.div
  ok  //此时.div的childs[0]是：Statement, Statement的content才是 InterpolatedString； render时，有换行效果，见 RendererAbstract::leaveStatement 
```

收获：
1. MtHaml换行的输出，可能都是通过renderer的leaveX而不是enterX
2. 行内方式提供的文本，不会被缩进(不是独占一行,没必要缩进)，文本末尾不会输出换行



理解MtHamlSnip中，文本输出的换行、缩进效果
---
```
--- snip ----
$snip=<<<SNIP
#head
  hello world!
  @@@
  @@@
  @@@name1
  @@@name2
SNIP;
---- haml ---
#me
  @snip
    _ ok
    _ yes
    _name1
      33
    _name2
      3
  .last
--- output ---
<div id="me">
  <div id="head">
    hello world!
okyes    33
    3
  </div>
  <div class="last"></div>
</div>
```

--- 解释 ---

ok和yes 不被Statement包裹，所以没有缩进，无末尾也不带换行

33和3 被Statement包裹，所以有缩进，末尾有换行



