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

class RecordView
{
    use SubdefsAware;
    use CaptionAware;

    /**
     * @var \record_adapter
     */
    protected $record;

    /**
     * @var TechnicalDataView
     */
    private $technicalDataView;

    public function __construct(\record_adapter $record)
    {
        $this->record = $record;
    }

    /**
     * @return \record_adapter
     */
    public function getRecord()
    {
        return $this->record;
    }

    public function setTechnicalDataView(TechnicalDataView $technicalDataView)
    {
        $this->technicalDataView = $technicalDataView;
    }

    /**
     * @return TechnicalDataView
     */
    public function getTechnicalDataView()
    {
        return $this->technicalDataView;
    }
}
