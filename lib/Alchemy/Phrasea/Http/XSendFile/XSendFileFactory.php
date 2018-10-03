<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Psr\Log\LoggerInterface;

class XSendFileFactory
{
    private $enabled;
    private $logger;
    private $type;
    private $mapping;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param boolean         $enabled
     * @param string          $type
     * @param array           $mapping
     */
    public function __construct(LoggerInterface $logger, $enabled, $type, array $mapping)
    {
        $this->logger = $logger;
        $this->enabled = (Boolean) $enabled;
        $this->type = strtolower($type);
        $this->mapping = $mapping;
    }

    /**
     * Creates a new instance of XSendFile Factory according to the application
     * configuration.
     *
     * @param  Application      $app
     * @return XSendFileFactory
     */
    public static function create(Application $app)
    {
        $conf = $app['conf']->get('xsendfile');

        $mapping = [];

        if (isset($conf['mapping'])) {
            $mapping = $conf['mapping'];
        }

        return new self($app['monolog'], $conf['enabled'], $conf['type'], $mapping);
    }

    /**
     * Returns a new instance of ModeInterface.
     *
     * @return ModeInterface
     *
     * @throws InvalidArgumentException if mode type is unknown
     */
    public function getMode($throwException = false, $forceMode = false)
    {
        if (false === $this->enabled && true !== $forceMode) {
            return new NullMode();
        }

        switch ($this->type) {
            case 'nginx':
            case 'sendfile':
            case 'xaccel':
            case 'xaccelredirect':
            case 'x-accel':
            case 'x-accel-redirect':
                if (2 >= count($this->mapping)) {
                    $this->logger->error('Invalid xsendfile mapping configuration.');
                    if ($throwException) {
                        throw new RuntimeException('Mapping is not set up.');
                    }
                }

                return new NginxMode($this->mapping);
            case 'apache':
            case 'apache2':
            case 'xsendfile':
                return new ApacheMode($this->mapping);
            default:
                $this->logger->error('Invalid xsendfile type configuration.');
                if ($throwException) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid xsendfile type value "%s"',
                        $this->type
                    ));
                }

                return new NullMode();
        }
    }

    /**
     * @return Boolean
     */
    public function isXSendFileModeEnabled()
    {
        return $this->enabled;
    }
}
