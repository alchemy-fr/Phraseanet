<?php

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Model\RecordInterface;

class RecordAutoSubtitleEvent extends RecordEvent
{
    private $languageSource;
    private $metaStructureIdSource;
    private $languageDestination;
    private $metaStructureIdDestination;

    public function __construct(
        RecordInterface $record,
        $languageSource,
        $metaStructureIdSource,
        $languageDestination
    )
    {
        parent::__construct($record);

        $this->languageSource               = $languageSource;
        $this->metaStructureIdSource        = $metaStructureIdSource;
        $this->languageDestination          = $languageDestination;
    }

    public function getLanguageSource()
    {
        return $this->languageSource;
    }

    public function getMetaStructureIdSource()
    {
        return $this->metaStructureIdSource;
    }

    public function getLanguageDestination()
    {
        return $this->languageDestination;
    }

    public function getMetaStructureIdDestination()
    {
        return $this->metaStructureIdDestination;
    }
}
