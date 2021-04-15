<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;

class Concept
{
    private $databox_id;
    private $path;

    public function __construct($databox_id, $path)
    {
        $this->databox_id = $databox_id;
        $this->path = (string) $path;
    }

    public function getDataboxId()
    {
        return $this->databox_id;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function isNarrowerThan(Concept $other)
    {
        // A concept is the child of another if it begins with the other
        return 0 === strpos($this->getPath(), $other->getPath() . '/');
    }

    public function __toString()
    {
        return $this->path;
    }

    public static function toPathArray(array $concepts)
    {
        foreach ($concepts as $index => $concept) {
            $concepts[$index] = $concept->getPath();
        }
        return $concepts;
    }

    public static function pruneNarrowConcepts($concepts)
    {
        // Build a map with paths as keys
        $concepts = array_combine(Concept::toPathArray($concepts), $concepts);
        // Paths are sorted in advance to keep search O(n)
        ksort($concepts);
        // With sorting, the first element can't be a child
        $broad = current($concepts);
        next($concepts);
        // Start prunning concepts narrower than current broad one
        while ($concept = current($concepts)) {
            if ($concept->isNarrowerThan($broad)) {
                unset($concepts[key($concepts)]);
            } else {
                // End of prunable childs, beginning of a new concept
                $broad = $concept;
                next($concepts);
            }
        }

        return array_values($concepts);
    }
}
