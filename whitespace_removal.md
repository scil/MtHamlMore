failure try
===
failure try to make < and > work around SnipCaller or Placeholder.
snip and Placeholder values are rendered independently, 
it's hard to let < or > work, especially when node tree is complicated like test 4.1.use snip in snip..11.test
so i give up.

thinking
===
1. make transparent node like VirtualRoot or VirtualPlaceholder
2. how to achive transparency? hack node relation (getParent/getNextSibling/getPreviousSibling) . see : hackNodeAbstract.php

process
===
two test "7.1.INNER flag and snipcaller..21.test" and "7.2.INNER flag and placeholder..22".test is ok
but test "4.1.use snip in snip..11.test" run infinitely. 
and test 4.2 and 4.3 and some other tests has same problems.

A Shining Point
===
how to hack MtHaml file? use composer.json