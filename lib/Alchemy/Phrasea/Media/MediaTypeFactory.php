<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

class MediaTypeFactory
{
    /**
     * @param string $mediaType
     * @return Type\Type
     */
    public function createMediaType($mediaType)
    {
        switch (strtolower($mediaType))
        {
            case Type\Type::TYPE_AUDIO:
                return new Type\Audio();
            case Type\Type::TYPE_IMAGE:
                return new Type\Image();
            case Type\Type::TYPE_VIDEO:
                return new Type\Video();
            case Type\Type::TYPE_DOCUMENT:
                return new Type\Document();
            case Type\Type::TYPE_FLASH:
                return new Type\Flash();
            case Type\Type::TYPE_UNKNOWN:
                return new Type\Unknown();
        }

        throw new \RuntimeException('Could not create requested media type');
    }
}
