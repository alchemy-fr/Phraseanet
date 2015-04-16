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

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PluginRepository
{
    const PHRASEANET_PLUGIN_PREFIX_LENGTH = 18;

    /** @var string|string[] */
    private $pluginsDirectories;

    /**
     * @param string|string[] $pluginsDirectories
     */
    public function __construct($pluginsDirectories)
    {
        $this->pluginsDirectories = $pluginsDirectories;
    }

    /**
     * @param string $name
     * @return bool|mixed
     * @throws PluginException
     */
    public function find($name)
    {
        $name = strtolower($name);
        if ('' == $name && false !== strpos($name, '/')) {
            throw new PluginException('Plugin with name "'. $name . '" is invalid.');
        }

        $finder = $this->createFinder()->path($name . '/composer.json');
        $plugins = iterator_to_array($this->gatherFoundPackages($finder, 1));
        if (empty($plugins)) {
            throw new PluginException('Plugin with name "'. $name . '" was not found.');
        }

        if ($name !== $key = key($plugins)) {
            throw new \RuntimeException('Plugin name mismatch, expected ' . $name . ', got '. $key);
        }

        return current($plugins);
    }

    public function findAll()
    {
        $finder = $this->createFinder()->name('composer.json');
        return $this->gatherFoundPackages($finder);
    }

    /**
     * @return Finder
     */
    private function createFinder()
    {
        return Finder::create()
            ->files()
            ->in($this->pluginsDirectories)
            ->depth(1);
    }

    /**
     * @param Finder $finder
     * @param int    $limit
     * @return \Iterator[]
     * @throws PluginException
     */
    private function gatherFoundPackages(Finder $finder, $limit = 0)
    {
        $found = 0;
        /** @var SplFileInfo $fileInfo */
        foreach ($finder as $fileInfo) {
            $contents = json_decode($fileInfo->getContents(), true);
            if (!(
                is_array($contents)
                && isset($contents['name'])
                && is_string($contents['name'])
                && isset($contents['extra']['class'])
                && is_string($contents['extra']['class'])
            )) {
                throw new PluginException(sprintf('Expects %s to be a valid plugin manifest, got "%s".', $fileInfo->getPath(), $fileInfo->getContents()));
            }
            $name = strtolower(substr($contents['name'], self::PHRASEANET_PLUGIN_PREFIX_LENGTH));
            $class = $contents['extra']['class'];
            if (!class_exists($class) || !in_array(Plugin::class, class_parents($class))) {
                throw new PluginException(sprintf('Expects "%s" to be a valid class name extending %s.', $class, Plugin::class));
            }

            yield  $name => $class;

            if ($limit && ++$found >= $limit) {
                return;
            }
        }
    }
}
