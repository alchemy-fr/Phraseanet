<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
