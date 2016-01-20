<?php

namespace Alchemy\Phrasea\Core\Profiler\Cache;

use Alchemy\Phrasea\Cache\Factory;

class TraceableCacheFactory extends Factory
{

    private $factory;

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string $name
     * @param array $options
     * @return TraceableCache
     */
    public function create($name, $options)
    {
        return new TraceableCache($this->factory->create($name, $options));
    }
}
