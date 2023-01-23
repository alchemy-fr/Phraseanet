<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

interface ConfigurationInterface extends \ArrayAccess
{
    /**
     * Do not compile, just save the config
     *
     * @param bool $noCompile
     */
    public function setNoCompile(bool $noCompile);

    /**
     * Initialize configuration file in setup.
     *
     * Creates the configuration, compiles it.
     *
     * @return ConfigurationInterface
     */
    public function initialize();

    /**
     * Deletes the current configuration.
     *
     * @return ConfigurationInterface
     */
    public function delete();

    /**
     * Returns true if a configuration exists.
     *
     * @return Boolean
     */
    public function isSetup();

    /**
     * Reset a configuration key to the default one.
     *
     * @return ConfigurationInterface
     */
    public function setDefault($name);

    /**
     * Gets the configuration values as array
     *
     * @return array
     */
    public function getConfig();

    /**
     * Sets the configuration
     *
     * @param array $config
     *
     * @return ConfigurationInterface
     */
    public function setConfig(array $config);

    /**
     * Load YAML configuration, compiles and writes.
     */
    public function compileAndWrite();
}
