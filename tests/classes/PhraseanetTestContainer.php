<?php

use Pimple\Container;
use Pimple\Exception\FrozenServiceException;

/**
 * Test fixtures container: fixtures can be redefined after they have been used.
 */
class PhraseanetTestContainer extends Container
{
    public function offsetSet($id, $value): void
    {
        try {
            parent::offsetSet($id, $value);
        } catch (FrozenServiceException $e) {
            parent::offsetUnset($id);
            parent::offsetSet($id, $value);
        }
    }
}
