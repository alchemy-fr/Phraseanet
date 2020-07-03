<?php

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Model\RecordInterface;

class RecordAutoSubtitleEvent extends RecordEvent
{
    private $languageSource;
    private $metaStructId;

    public function __construct(RecordInterface $record, $languageSource, $metaStructId)
    {
        parent::__construct($record);

        $this->languageSource = $languageSource;
        $this->metaStructId   = $metaStructId;
    }

    public function getLanguageSource()
    {
        return $this->languageSource;
    }

    public function getMetaStructId()
    {
        return $this->metaStructId;
    }
}
