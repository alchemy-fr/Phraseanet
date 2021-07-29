<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

use MediaVorus\Media\MediaInterface;

class SubDefinitionCreatedEvent extends RecordEvent
{
    private $subDefinitionName;
    private $media;

    public function __construct(\record_adapter $record, $subDefinitionName, MediaInterface $media)
    {
        parent::__construct($record);

        $this->subDefinitionName = $subDefinitionName;
        $this->media = $media;
    }

    /**
     * @return string
     */
    public function getSubDefinitionName()
    {
        return $this->subDefinitionName;
    }

    /**
     * @return MediaInterface
     */
    public function getMedia()
    {
        return $this->media;
    }
}
