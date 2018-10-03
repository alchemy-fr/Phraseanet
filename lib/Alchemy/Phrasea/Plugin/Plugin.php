<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin;

use Alchemy\Phrasea\Plugin\Exception\PluginValidationException;
use Alchemy\Phrasea\Plugin\Schema\Manifest;

class Plugin
{
    private $error;
    private $manifest;
    private $name;

    public function __construct($name, Manifest $manifest = null, PluginValidationException $error = null)
    {
        if ($manifest === $error || (null !== $manifest && null !== $error)) {
            throw new \LogicException('A plugin is either installed (with a stable manifest) or on error (given its error).');
        }

        $this->name = $name;
        $this->manifest = $manifest;
        $this->error = $error;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Boolean
     */
    public function isErroneous()
    {
        return null !== $this->error;
    }

    /**
     * @return Manifest
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * @return PluginValidationException
     */
    public function getError()
    {
        return $this->error;
    }
}
