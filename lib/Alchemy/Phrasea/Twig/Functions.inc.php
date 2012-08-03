<?php

function is_loopable($item)
{
    return is_array($item) || $item instanceof Traversable;
}
