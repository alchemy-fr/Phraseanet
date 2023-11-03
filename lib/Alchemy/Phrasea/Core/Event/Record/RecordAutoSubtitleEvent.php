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
        $languageDestination
    )
    {
        parent::__construct($record);

        $this->languageSource               = $languageSource;
        $this->languageDestination          = $languageDestination;
    }

    public function getLanguageSource()
    {
        return $this->languageSource;
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
