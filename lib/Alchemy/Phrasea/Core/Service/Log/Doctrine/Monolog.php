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

use Alchemy\Phrasea\Core\Service\Log\Monolog as ParentLog;
use Doctrine\Logger\MonologSQLLogger;
/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Monolog extends ParentLog implements ServiceInterface
{
  const JSON_OUTPUT = 'json';
  const YAML_OUTPUT = 'yaml';
  const VAR_DUMP_OUTPUT = 'normal';
  
  protected $outputs = array(
      self::JSON_OUTPUT, self::YAML_OUTPUT, self::VAR_DUMP_OUTPUT
  );
  
  public function getService()
  {
    $output = isset($this->options["output"]) ? $this->options["output"] : self::JSON_OUTPUT;

    if (!in_array($output, $this->outputs))
    {
      throw new \Exception(sprintf('Unknow log output class %s', $output));
    }

    return new MonologSQLLogger($this->monolog, $output);
  }
  
  public function getType()
  {
    return 'doctrine_monolog';
  }
  
}