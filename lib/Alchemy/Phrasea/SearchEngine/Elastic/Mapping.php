<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

class Mapping
{
    private $fields = array();
    private $current;

    public function add($name, $type)
    {
        // TODO Check input
        $this->fields[$name] = array('type' => $type);
        $this->current = $name;

        return $this;
    }

    public function export()
    {
        return ['properties' => $this->fields];
    }

    public function notAnalyzed()
    {
        $field =& $this->currentField();
        if ($field['type'] !== 'string') {
            throw new \LogicException('Only string fields can be not analyzed');
        }
        $field['index'] = 'not_analyzed';

        return $this;
    }

    public function format($format)
    {
        $field =& $this->currentField();
        if ($field['type'] !== 'date') {
            throw new \LogicException('Only date fields can have a format');
        }
        $field['format'] = $format;

        return $this;
    }

    protected function &currentField()
    {
        if (null === $this->current) {
            throw new \LogicException('You must add a field first');
        }

        return $this->fields[$this->current];
    }
}
