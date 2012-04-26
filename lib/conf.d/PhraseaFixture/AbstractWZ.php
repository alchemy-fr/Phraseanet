<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture;

use Doctrine\Common\DataFixtures\AbstractFixture;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class AbstractWZ extends AbstractFixture
{
    protected $user;
    protected $record;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(\User_Adapter $user)
    {
        $this->user = $user;
    }

    public function getRecord()
    {
        return $this->record;
    }

    public function setRecord(\record_adapter $record)
    {
        $this->record = $record;
    }
}
