<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Http\StaticFile\Symlink\SymLinker;
use Psr\Log\LoggerInterface;

class StaticFileFactory
{
    private $enabled;
    private $logger;
    private $type;
    /** @var Symlink\SymLinker */
    private $symlinker;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param bool            $enabled
     * @param string          $type
     * @param SymLinker       $symlinker
     */
    public function __construct(LoggerInterface $logger, $enabled, $type, SymLinker $symlinker)
    {
        $this->logger = $logger;
        $this->enabled = (Boolean) $enabled;
        $this->type = strtolower($type);
        $this->symlinker = $symlinker;

        $this->mapping = array(
            'mount-point' => $symlinker->getDefaultAlias(),
            'directory' => $symlinker->getSymlinkDir()
        );
    }

    /**
     * Creates a new instance of StaticFileFactory Factory according to the application
     * configuration.
     *
     * @param  Application $app
     * @return StaticFileFactory
     */
    public static function create(Application $app)
    {
        $conf = $app['phraseanet.configuration']['static-file'];

        return new self($app['monolog'], $conf['enabled'], $conf['type'], $app['phraseanet.thumb-symlinker']);
    }

    /**
     * Returns a new instance of ModeInterface
     *
     * @param bool $throwException
     * @param bool $forceMode
     *
     * @return Apache|Nginx|NullMode
     * @throws InvalidArgumentException
     */
    public function getMode($throwException = false, $forceMode = false)
    {
        if (false === $this->enabled && true !== $forceMode) {
            return new NullMode();
        }

        switch ($this->type) {
            case 'nginx':
                return new Nginx($this->mapping, $this->symlinker);
                break;
            case 'apache':
            case 'apache2':
                return new Apache($this->mapping, $this->symlinker);
            default:
                $this->logger->error('Invalid static file configuration.');
                if ($throwException) {
                    throw new InvalidArgumentException(sprintf(
                        'Invalid static file type value "%s"',
                        $this->type
                    ));
                }

                return new NullMode();
        }
    }

    /**
     * @return bool
     */
    public function isStaticFileModeEnabled()
    {
        return $this->enabled;
    }
}
