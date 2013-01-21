<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Log;

use Alchemy\Phrasea\Core\Service\ServiceAbstract;
use Monolog\Logger;
use Monolog\Handler\FirePHPHandler;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class FirePHP extends ServiceAbstract
{
    protected $logger;

    public function getDriver()
    {
        if ( ! $this->logger) {
            $this->logger = new Logger('FirePHP');

            $this->logger->pushHandler(new FirePHPHandler());
        }

        return $this->logger;
    }

    public function getType()
    {
        return 'FirePHP Monolog';
    }
}
