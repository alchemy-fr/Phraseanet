<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2011 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Simple registry object for setup when appbox registry does not exists yet
 *
 * @package     Setup
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Setup_Registry implements registryInterface
{
    protected $datas = array();

    public function get($key, $defaultvalue = null)
    {
        return isset($this->datas[$key]) ? $this->datas[$key] : $defaultvalue;
    }

    public function set($key, $value, $type)
    {
        $this->datas[$key] = $value;
    }

    public function is_set($key)
    {
        return isset($this->datas[$key]);
    }

    public function un_set($key)
    {
        if (isset($this->datas[$key]))
            unset($datas[$key]);

        return $this;
    }
}
