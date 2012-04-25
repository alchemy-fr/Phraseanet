<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     task_manager
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_test extends task_appboxAbstract
{

    public function getName()
    {
        return "Test";
    }

    public function help()
    {
        return "just saying what i'm doing";
    }

    protected function retrieve_content(appbox $appbox)
    {
        $this->log('test class, retrive content');

        return array('hello', 'world');
    }

    protected function process_one_content(appbox $appbox, Array $row)
    {
        $this->log(sprintf("test class, process content : `%s`", implode(' ', $row)));

        return $this;
    }

    protected function post_process_one_content(appbox $appbox, Array $row)
    {
        $this->log(sprintf("test class, post process content, they were %s", count($row)));

        return $this;
    }
}
