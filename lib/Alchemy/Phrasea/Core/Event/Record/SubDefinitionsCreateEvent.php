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

use Alchemy\Phrasea\Application;

class SubDefinitionsCreateEvent extends RecordEvent
{
    private $subDefinitionsNames;

    public function __construct(\record_adapter $record, $subDefinitionsNames = [])
    {
        parent::__construct($record);

        $this->subDefinitionsNames = $subDefinitionsNames;
    }

    /**
     * @return string[]
     */
    public function getSubDefinitionsNames()
    {
        return $this->subDefinitionsNames;
    }

    protected function getData()
    {
        return [
            '_' => parent::getData(),
            'subDefinitionsNames' => $this->subDefinitionsNames
        ];
    }

    protected function restoreData($data, Application $app)
    {
        parent::restoreData($data['_'], $app);
        $this->subDefinitionsNames = $data['subDefinitionsNames'];
    }
}
