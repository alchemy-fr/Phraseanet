<?php

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Model\RecordInterface;

class RecordAutoSubtitleEvent extends RecordEvent
{
    private $languageSource;
    private $languageDestination;
    private $authenticatedUserId;

    public function __construct(
        RecordInterface $record,
        $languageSource,
        $languageDestination,
        $authenticatedUserId
    )
    {
        parent::__construct($record);

        $this->languageSource               = $languageSource;
        $this->languageDestination          = $languageDestination;
        $this->authenticatedUserId          = $authenticatedUserId;
    }

    public function getLanguageSource()
    {
        return $this->languageSource;
    }

    public function getLanguageDestination()
    {
        return $this->languageDestination;
    }

    public function getAuthenticatedUserId()
    {
        return $this->authenticatedUserId;
    }
}
