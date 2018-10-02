<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

interface cache_cacheableInterface
{

    public function get_cache_key($option = null);

    public function get_data_from_cache($option = null);

    public function set_data_to_cache($value, $option = null, $duration = 0);

    public function delete_data_from_cache($option = null);
}
