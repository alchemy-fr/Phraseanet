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

class SubDefinitionsCreatedEvent extends RecordEvent
{
    /** @var MediaInterface[] */
    private $media;

    /**
     * @param \record_adapter $record
     * @param MediaInterface[] $media
     */
    public function __construct(\record_adapter $record, array $media)
    {
        parent::__construct($record);

        $this->media = $media;
    }

    /**
     * @return MediaInterface[]
     */
    public function getMedia()
    {
        return $this->media;
    }
}
