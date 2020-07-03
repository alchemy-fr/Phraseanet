<?php

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Model\RecordInterface;

class RecordAutoSubtitleEvent extends RecordEvent
{
    private $languageSource;
    private $metaStructId;
    private $permalinkUrl;

    public function __construct(RecordInterface $record, $permalinkUrl, $languageSource, $metaStructId)
    {
        parent::__construct($record);

        $this->languageSource = $languageSource;
        $this->metaStructId   = $metaStructId;
        $this->permalinkUrl   = $permalinkUrl;
    }

    public function getLanguageSource()
    {
        return $this->languageSource;
    }

    public function getMetaStructId()
    {
        return $this->metaStructId;
    }

    public function getPermalinkUrl()
    {
        return $this->permalinkUrl;
    }
}
