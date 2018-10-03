<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Alchemy\Phrasea\Media\TechnicalDataSet;

class TechnicalDataView
{
    /**
     * @var TechnicalDataSet
     */
    private $dataSet;

    public function __construct(TechnicalDataSet $dataSet)
    {
        $this->dataSet = $dataSet;
    }

    /**
     * @return TechnicalDataSet
     */
    public function getDataSet()
    {
        return $this->dataSet;
    }
}
