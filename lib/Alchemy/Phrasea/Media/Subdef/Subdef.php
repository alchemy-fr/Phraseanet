<?php

namespace Alchemy\Phrasea\Media\Subdef;

interface Subdef
{

    const TYPE_IMAGE     = 'image';
    const TYPE_ANIMATION = 'gif';
    const TYPE_VIDEO     = 'video';
    const TYPE_AUDIO     = 'audio';
    const TYPE_FLEXPAPER = 'flexpaper';

    public function getType();

    public function getDescription();

    public function getMediaAlchemystSpec();

}
