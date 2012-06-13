<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Doctrine\ORM\EntityManager;

class MediaType implements Checker
{
    protected $mediaTypes;

    const TYPE_AUDIO = \MediaVorus\Media\Media::TYPE_AUDIO;
    const TYPE_DOCUMENT = \MediaVorus\Media\Media::TYPE_DOCUMENT;
    const TYPE_FLASH = \MediaVorus\Media\Media::TYPE_FLASH;
    const TYPE_IMAGE = \MediaVorus\Media\Media::TYPE_IMAGE;
    const TYPE_VIDEO = \MediaVorus\Media\Media::TYPE_VIDEO;

    public function __construct(array $options)
    {
        if ( ! isset($options['mediatypes'])) {
            throw new \InvalidArgumentException('Missing "mediatypes" options');
        }

        $this->mediaTypes = (array) $options['mediatypes'];
    }

    public function check(EntityManager $em, File $file)
    {
        if (0 === count($this->mediaTypes)) { //if empty authorize all mediative
            $boolean = true;
        } else {
            $boolean = in_array($file->getMedia()->getType(), $this->mediaTypes);
        }

        return new Response($boolean, $this);
    }

    public static function getMessage()
    {
        return _('The file does not match required media type');
    }
}
