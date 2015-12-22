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

class SubDefinitionCreationEvent extends RecordEvent
{
    private $subDefinitionName;

    public function __construct(\record_adapter $record, $subDefinitionName)
    {
        parent::__construct($record);

        $this->subDefinitionName = $subDefinitionName;
    }

    /**
     * @return string
     */
    public function getSubDefinitionName()
    {
        return $this->subDefinitionName;
    }
}
