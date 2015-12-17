<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

class SubDefinitionsCreationEvent extends RecordEvent
{
    private $subDefinitionsNames;

    public function __construct(\record_adapter $record, $subDefinitionsNames)
    {
        parent::__construct($record);

        $this->subDefinitiosnNames = $subDefinitionsNames;
    }

    /**
     * @return string
     */
    public function getSubDefinitionsNames()
    {
        return $this->subDefinitionsNames;
    }
}
