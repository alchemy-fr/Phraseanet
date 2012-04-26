<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Monolog extends ParentLog
{
    const JSON_OUTPUT = 'json';
    const YAML_OUTPUT = 'yaml';
    const VAR_DUMP_OUTPUT = 'vdump';

    public function getDriver()
    {
        $output = isset($this->options["output"]) ? $this->options["output"] : self::JSON_OUTPUT;

        $outputs = array(
            self::JSON_OUTPUT, self::YAML_OUTPUT, self::VAR_DUMP_OUTPUT
        );

        if ( ! in_array($output, $outputs)) {
            throw new \Exception(sprintf(
                    "The output type '%s' declared in %s service is not valid.
          Available types are %s."
                    , $output
                    , __CLASS__
                    , implode(", ", $outputs)
                )
            );
        }

        return new MonologSQLLogger($this->monolog, $output);
    }

    public function getType()
    {
        return 'doctrine_monolog';
    }
}
