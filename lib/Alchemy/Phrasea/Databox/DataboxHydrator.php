<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

class DataboxHydrator
{
    /** @var DataboxFactory */
    private $factory;

    public function __construct(DataboxFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param int   $id
     * @param array $row
     * @return \databox
     */
    public function hydrateRow($id, array $row)
    {
        return $this->factory->create($id, $row);
    }

    /**
     * Hydrate a list of databoxes keyed by their sbas_id
     * @param array $rows
     * @return \databox[]
     */
    public function hydrateRows($rows)
    {
        $instances = array();

        foreach ($rows as $id => $row) {
            $instances[$id] = $this->hydrateRow($id, $row);
        }

        return $instances;
    }
}
