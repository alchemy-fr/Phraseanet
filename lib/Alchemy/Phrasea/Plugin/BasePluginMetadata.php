<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Plugin;

use Assert\Assertion;

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
    /** @var ConfigurationTabInterface[] */
    private $configurationTabs;

    /**
     * @param string $name
     * @param string $version
     * @param string $iconUrl
     * @param string $localeTextDomain
     */
    public function __construct($name, $version, $iconUrl, $localeTextDomain)
    {
        $this->name = $name;
        $this->version = $version;
        $this->iconUrl = $iconUrl;
        $this->localeTextDomain = $localeTextDomain;
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
     * @param ConfigurationTabInterface[] $tabs
     */
    public function setConfigurationTabs($tabs)
    {
        Assertion::allIsInstanceOf($tabs, 'Alchemy\Phrasea\Plugin\ConfigurationTabInterface');

        foreach ($tabs as $name => $tab) {
            $this->addConfigurationTab($name, $tab);
        }
    }

    /**
     * @param string                    $name
     * @param ConfigurationTabInterface $tab
     */
    public function addConfigurationTab($name, ConfigurationTabInterface $tab)
    {
        Assertion::regex($name, '/^[a-zA-Z][-_a-zA-Z0-9]*$/');
        if (isset($this->configurationTabs[$name])) {
            throw new \LogicException(sprintf(
                'A configuration tab with name "%s" is already defined. Registered tabs: "%s"',
                implode('", "', array_keys($this->configurationTabs))
            ));
        }

        $this->configurationTabs[$name] = $tab;
    }

    public function getConfigurationTabs()
    {
        return $this->configurationTabs;
    }
}
