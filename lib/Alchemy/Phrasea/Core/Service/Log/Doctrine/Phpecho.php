<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Log\Doctrine;

use Alchemy\Phrasea\Core\Service\ServiceAbstract;
use Doctrine\DBAL\Logging\EchoSQLLogger;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Phpecho extends ServiceAbstract
{

    public function getDriver()
    {
        return new EchoSQLLogger();
    }

    public function getType()
    {
        return 'phpecho';
    }
}
