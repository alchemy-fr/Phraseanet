<?php

namespace Alchemy\Phrasea\Databox;

class DataboxConnectionProvider 
{

    private $applicationBox;

    public function __construct(\appbox $applicationBox)
    {
        $this->applicationBox = $applicationBox;
    }

    /**
     * @param $databoxId
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection($databoxId)
    {
        return $this->applicationBox->get_databox($databoxId)->get_connection();
    }
}
