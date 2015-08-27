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

class BasePluginMetadata implements PluginMetadataInterface
{
    /** @var string */
    private $name;
    /** @var string */
    private $version;
    /** @var string */
    private $iconUrl;

    /**
     * @param string $name
     * @param string $version
     * @param string $iconUrl
     */
    public function __construct($name, $version, $iconUrl)
    {
        $this->name = $name;
        $this->version = $version;
        $this->iconUrl = $iconUrl;
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
}
