<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Cors\Options;

use Symfony\Component\HttpFoundation\Request;

/**
 * Default CORS configuration provider
 *
 * Use the library's semantic configuration.
 * Default settings are the lowest priority one, and can be relied upon.
 */
class DefaultProvider implements Provider
{
    /** @var array */
    private $paths;
    /** @var array */
    private $defaults;

    public function __construct(array $paths, array $defaults)
    {
        $this->paths = $paths;
        $this->defaults = $defaults;
    }

    public function getOptionsFor(Request $request)
    {
        $uri = $request->getPathInfo() ?: '/';
        foreach ($this->paths as $pathRegex => $options) {
            if (preg_match('{' . $pathRegex . '}i', $uri)) {
                $options = array_merge($this->defaults, $options);

                if ($options['hosts']) {
                    foreach ($options['hosts'] as $hostRegex) {
                        if (preg_match('{' . $hostRegex . '}i', $request->getHost())) {
                            return $options;
                        }
                    }

                    continue;
                }

                return $options;
            }
        }

        return $this->defaults;
    }
}
