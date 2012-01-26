<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\Log\Doctrine;

use Alchemy\Phrasea\Core,
    Alchemy\Phrasea\Core\Service,
    Alchemy\Phrasea\Core\Service\ServiceAbstract,
    Alchemy\Phrasea\Core\Service\ServiceInterface;

use Doctrine\DBAL\Logging\EchoSQLLogger;
/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Phpecho extends ServiceAbstract implements ServiceInterface
{


  public function getService()
  {
    return new EchoSQLLogger();
  }

  public function getType()
  {
    return 'phpecho';
  }

  public function getScope()
  {
    return 'log';
  }

}
