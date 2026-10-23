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

class DefaultResolver implements Resolver
{
    /** @var Provider[]|array */
    private $providers;

    /**
     * @param Provider[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function getOptionsFor(Request $request)
    {
        $options = array();

        foreach ($this->providers as $provider) {
            $options = array_merge($options, $provider->getOptionsFor($request));
        }

        return $options;
    }
}
