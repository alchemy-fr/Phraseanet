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
 * @package     Setup
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Setup_Constraint
{
    protected $name;
    protected $success;
    protected $message;
    protected $blocker;

    public function __construct($name, $success, $message, $blocker = false)
    {
        $this->name = $name;
        $this->success = ! ! $success;
        $this->message = $message;
        $this->blocker = ! ! $blocker;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     *
     * @return boolean
     */
    public function is_ok()
    {
        return $this->success;
    }

    /**
     *
     * @return boolean
     */
    public function is_blocker()
    {
        return $this->blocker;
    }

    /**
     *
     * @return string
     */
    public function get_message()
    {
        return $this->message;
    }
}
