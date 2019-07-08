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
use Alchemy\Phrasea\Core\Event\WorkerableEventInterface;


class SubDefinitionsCreateEvent extends RecordEvent implements WorkerableEventInterface
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



    /** **************** WorkerableEventInterface interface ************** */

    public function getAsScalars()
    {
        return [
            '_' => parent::getAsScalars(),
            'subDefinitionsNames' => $this->subDefinitionsNames
        ];
    }

    public function restoreFromScalars($data, Application $app)
    {
        parent::restoreFromScalars($data['_'], $app);
        $this->subDefinitionsNames = $data['subDefinitionsNames'];
    }

    /** ****************************************************************** */
}
