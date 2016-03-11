<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Checker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Doctrine\ORM\EntityManager;
use MediaVorus\Media\MediaInterface;
use Symfony\Component\Translation\TranslatorInterface;

class MediaType extends AbstractChecker
{
    protected $mediaTypes;

    const TYPE_AUDIO = MediaInterface::TYPE_AUDIO;
    const TYPE_DOCUMENT = MediaInterface::TYPE_DOCUMENT;
    const TYPE_FLASH = MediaInterface::TYPE_FLASH;
    const TYPE_IMAGE = MediaInterface::TYPE_IMAGE;
    const TYPE_VIDEO = MediaInterface::TYPE_VIDEO;

    public function __construct(Application $app, array $options)
    {
        if (!isset($options['mediatypes'])) {
            throw new \InvalidArgumentException('Missing "mediatypes" options');
        }

        $this->mediaTypes = (array) $options['mediatypes'];
        parent::__construct($app);
    }

    public function check(EntityManager $em, File $file)
    {
        // if empty authorize all mediative
        if (0 === count($this->mediaTypes)) {
            $boolean = true;
        } else {
            $boolean = in_array($file->getMedia()->getType(), $this->mediaTypes);
        }

        return new Response($boolean, $this);
    }

    public function getMessage(TranslatorInterface $translator)
    {
        return $translator->trans('The file does not match required media type');
    }
}
