<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media\Type;

interface Type
{

    const TYPE_AUDIO    = 'Audio';
    const TYPE_VIDEO    = 'Video';
    const TYPE_DOCUMENT = 'Document';
    const TYPE_FLASH    = 'Flash';
    const TYPE_IMAGE    = 'Image';

    public function getType();

}
