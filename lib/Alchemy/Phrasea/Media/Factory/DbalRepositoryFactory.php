<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Factory;

use Alchemy\Phrasea\Databox\DataboxConnectionProvider;
use Alchemy\Phrasea\Media\RecordTechnicalDataSetRepositoryFactory;
use Alchemy\Phrasea\Media\Repository\DbalRecordTechnicalDataSetRepository;

class DbalRepositoryFactory implements RecordTechnicalDataSetRepositoryFactory
{
    /**
     * @var DataboxConnectionProvider
     */
    private $connectionProvider;

    public function __construct(DataboxConnectionProvider $connectionProvider)
    {
        $this->connectionProvider = $connectionProvider;
    }

    public function createRepositoryForDatabox($databoxId)
    {
        return new DbalRecordTechnicalDataSetRepository(
            $this->connectionProvider->getConnection($databoxId),
            new TechnicalDataFactory()
        );
    }
}
