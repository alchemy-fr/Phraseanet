<?php

namespace Alchemy\Phrasea\Controller\Api\V3;


use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Media\MediaSubDefinitionUrlGenerator;
use databox_status;
use record_adapter;


class V3ResultHelpers
{
    /** @var controller */
    private $controller;

    /** @var MediaSubDefinitionUrlGenerator */
    private $urlgenerator;

    /** @var PropertyAccess */
    private $conf;


    public function __construct($controller, $conf, $urlgenerator)
    {
        $this->controller = $controller;
        $this->urlgenerator = $urlgenerator;
        $this->conf = $conf;
    }


    /**
     * Retrieve detailed information about one status
     *
     * @param record_adapter $record
     * @return array
     */
    public function listRecordStatus(record_adapter $record)
    {
        $ret = [];
        foreach ($record->getStatusStructure() as $bit => $status) {
            $ret[] = [
                'bit'   => $bit,
                'state' => databox_status::bitIsSet($record->getStatusBitField(), $bit),
            ];
        }

        return $ret;
    }

}
