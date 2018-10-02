<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

class RecordTechnicalDataSet extends ArrayTechnicalDataSet
{
    /**
     * @var int
     */
    private $recordId;

    /**
     * @param int $recordId
     * @param TechnicalData[] $technicalData
     */
    public function __construct($recordId, $technicalData = [])
    {
        $this->recordId = (int)$recordId;
        parent::__construct($technicalData);
    }

    /**
     * @return int
     */
    public function getRecordId()
    {
        return $this->recordId;
    }
}
