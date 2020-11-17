<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\StructureException;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use databox;

class FlagHydrator implements HydratorInterface
{
    /** @var array */
    private $field_names_map;

    public function __construct(Structure $structure, databox $databox)
    {
        $this->field_names_map = self::buildFieldNamesMap($structure, $databox);
    }

    private static function buildFieldNamesMap(Structure $structure, databox $databox)
    {
        $names_map = [];
        foreach ($structure->getAllFlags() as $name => $flag) {
            $bit = $flag->getBitPositionInDatabox($databox);
            if ($bit === null) {
                continue;
            }
            if (isset($names_map[$bit])) {
                throw new StructureException(sprintf('Duplicated flag for bit %d', $bit));
            }
            $names_map[$bit] = $name;
        }
        return $names_map;
    }

    public function hydrateRecords(array &$records)
    {
        foreach ($records as &$record) {
            if (isset($record['flags_bitfield'])) {
                $record['flags'] = $this->bitfieldToFlagsMap($record['flags_bitfield']);
            }
        }
    }

    private function bitfieldToFlagsMap($bitfield)
    {
        $flags = [];
        foreach ($this->field_names_map as $position => $name) {
            $flags[$name] = \databox_status::bitIsSet($bitfield, $position);
        }
        return $flags;
    }
}
