<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox;

class DataboxConnectionProvider 
{
    /**
     * @var \appbox
     */
    private $applicationBox;

    public function __construct(\appbox $applicationBox)
    {
        $this->applicationBox = $applicationBox;
    }

    /**
     * @param int $databoxId
     * @return \Doctrine\DBAL\Connection
     */
    public function getConnection($databoxId)
    {
        return $this->applicationBox->get_databox($databoxId)->get_connection();
    }
}
