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

interface PluginMetadataInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getVersion();

    /**
     * @return string
     */
    public function getIconUrl();

    /**
     * @return string
     */
    public function getLocaleTextDomain();

    /**
     * Names of all configuration tabs service.
     *
     * @return array<string>
     */
    public function getConfigurationTabServiceIds();
}
