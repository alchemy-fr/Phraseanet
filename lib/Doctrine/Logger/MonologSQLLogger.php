<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Log doctrine sql request with monolog
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MonologSQLLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    const JSON = 'json';
    const YAML = 'yaml';
    const VDUMP = 'vdump';

    /**
     *
     * @var \Monolog\Logger
     */
    private $logger;
    private $start;
    private $output = array();
    private $outputType;

    /**
     * Tell which monolog user to use and which format to output
     *
     * @param \Monolog\Logger $logger A monolog logger instance
     * @param type $type the output format
     */
    public function __construct(\Monolog\Logger $logger, $type = self::YAML)
    {
        $this->logger = $logger;
        $this->outputType = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->start = microtime(true);

        $this->output["sql"] = $sql;

        if ($params) {
            $this->output["params"] = $params;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        $mstime = microtime(true) - $this->start;

        $this->output["times"] = $mstime . " seconds";
        if ($this->outputType == self::JSON) {
            $this->log(json_encode($this->output));
        } elseif ($this->outputType == self::YAML) {
            $this->log(\Symfony\Component\Yaml\Yaml::dump($this->output));
        } else {
            $this->log(var_export($this->output, true));
        }
    }

    protected function log($message)
    {
        $this->logger->debug($message);
    }
}
