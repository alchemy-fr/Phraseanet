<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Status;

/**
 * This class is used to get databox status structure
 */
class StatusStructureFactory
{
    /**
     * Keep trace of already instantiated databox status structure
     *
     * @var array
     */
    private $statusStructure = [];

    /**
     * A provider that gives the definition of a structure
     *
     * @var StatusStructureProviderInterface
     */
    protected $provider;

    public function __construct(StatusStructureProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Get the status structure according to the given databox
     *
     * @param \databox $databox
     *
     * @return StatusStructure
     */
    public function getStructure(\databox $databox)
    {
        $databox_id = $databox->get_sbas_id();

        if (isset($this->statusStructure[$databox_id])) {
            return $this->statusStructure[$databox_id];
        }

        $this->statusStructure[$databox_id] = $this->provider->getStructure($databox);

        return $this->statusStructure[$databox_id];
    }
}
