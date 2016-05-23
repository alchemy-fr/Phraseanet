<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox\Caption;

use Alchemy\Phrasea\Databox\DataboxBoundRepositoryFactory;
use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Doctrine\Common\Cache\Cache;

class CaptionDataRepositoryFactory implements DataboxBoundRepositoryFactory
{
    /**
     * @var DataboxConnectionProvider
     */
    private $connectionProvider;

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(DataboxConnectionProvider $connectionProvider, Cache $cache)
    {
        $this->connectionProvider = $connectionProvider;
        $this->cache = $cache;
    }

    public function createRepositoryFor($databoxId)
    {
        return new CachedCaptionDataRepository(
            new DbalCaptionDataRepository($this->connectionProvider->getConnection($databoxId)),
            $this->cache,
            sprintf('databox[%d]:', $databoxId)
        );
    }
}
