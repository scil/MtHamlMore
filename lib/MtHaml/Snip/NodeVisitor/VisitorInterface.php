<?php

namespace MtHaml\Snip\NodeVisitor;

use MtHaml\Snip\Node\SnipCaller;
use MtHaml\Snip\Node\PlaceholderValue;
use MtHaml\Snip\Node\Placeholder;

interface VisitorInterface
{
    public function enterSnipCaller(SnipCaller $node);
    public function enterSnipCallerContent(SnipCaller $node);
    public function leaveSnipCallerContent(SnipCaller $node);
    public function enterSnipCallerChilds(SnipCaller $node);
    public function leaveSnipCallerChilds(SnipCaller $node);
    public function leaveSnipCaller(SnipCaller $node);


    public function enterPlaceholderValue(PlaceholderValue $node);
    public function enterPlaceholderValueContent(PlaceholderValue $node);
    public function leavePlaceholderValueContent(PlaceholderValue $node);
    public function enterPlaceholderValueChilds(PlaceholderValue $node);
    public function leavePlaceholderValueChilds(PlaceholderValue $node);
    public function leavePlaceholderValue(PlaceholderValue $node);

    public function enterPlaceholder(Placeholder $node);
    public function enterPlaceholderContent(Placeholder $node);
    public function leavePlaceholderContent(Placeholder $node);
    public function enterPlaceholderChilds(Placeholder $node);
    public function leavePlaceholderChilds(Placeholder $node);
    public function leavePlaceholder(Placeholder $node);
}
