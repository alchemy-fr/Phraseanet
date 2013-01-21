<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Exception
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Exception_Session_MailLocked extends Exception
{
    protected $usr_id;

    public function __construct($usr_id = null, $message = null, $code = null, $previous = null)
    {
        $this->usr_id = $usr_id;
        parent::__construct($message, $code, $previous);
    }

    public function get_usr_id()
    {
        return $this->usr_id;
    }
}
