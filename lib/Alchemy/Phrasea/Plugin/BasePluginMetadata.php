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

class BasePluginMetadata implements PluginMetadataInterface
{
    /** @var string */
    private $name;
    /** @var string */
    private $version;
    /** @var string */
    private $iconUrl;
    /** @var string */
    private $localeTextDomain;
    /** @var string[] */
    private $configurationTabServiceIds;

    /**
     * @param string   $name
     * @param string   $version
     * @param string   $iconUrl
     * @param string   $localeTextDomain
     * @param string[] $configurationTabServiceIds
     */
    public function __construct($name, $version, $iconUrl, $localeTextDomain, array $configurationTabServiceIds = [])
    {
        $this->name = $name;
        $this->version = $version;
        $this->iconUrl = $iconUrl;
        $this->localeTextDomain = $localeTextDomain;
        $this->configurationTabServiceIds = $configurationTabServiceIds;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        return $this->iconUrl;
    }

    /**
     * @return string
     */
    public function getLocaleTextDomain()
    {
        return $this->localeTextDomain;
    }

    /**
     * @return string[]
     */
    public function getConfigurationTabServiceIds()
    {
        return $this->configurationTabServiceIds;
    }
}
