<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface registryInterface
{

    public function get($key, $defaultvalue = null);

    public function set($key, $value, $type);

    public function is_set($key);

    public function un_set($key);
}
