<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class sphinx_charsetTableAbstract
{
    protected $table;
    protected $name;

    public function get_name()
    {
        return $this->name;
    }

    public function get_table()
    {
        if (is_null($this->table))
            throw new Exception('Invalid charsetTable object');

        return $this->table;
    }
}
