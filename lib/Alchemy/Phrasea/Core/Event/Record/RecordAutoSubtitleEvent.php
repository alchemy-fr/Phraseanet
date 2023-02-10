<?php

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Model\RecordInterface;

class RecordAutoSubtitleEvent extends RecordEvent
{
    private $subtitleProvider;
    private $languageSource;
    private $metaStructureIdSource;
    private $languageDestination;
    private $metaStructureIdDestination;

    public function __construct(
        RecordInterface $record,
        $subtitleProvider,
        $languageSource,
        $metaStructureIdSource,
        $languageDestination,
        $metaStructureIdDestination
    )
    {
        parent::__construct($record);

        $this->subtitleProvider             = $subtitleProvider;
        $this->languageSource               = $languageSource;
        $this->metaStructureIdSource        = $metaStructureIdSource;
        $this->languageDestination          = $languageDestination;
        $this->metaStructureIdDestination   = $metaStructureIdDestination;
    }

    public function getSubtitleProvider()
    {
        return $this->subtitleProvider;
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
