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
use MediaVorus\Media\Document;
use Symfony\Component\Translation\TranslatorInterface;

class Colorspace extends AbstractChecker
{
    protected $colorspaces;

    const COLORSPACE_RGB = 'rgb';
    const COLORSPACE_CMYK = 'cmyk';
    const COLORSPACE_GRAYSCALE = 'grayscale';
    const COLORSPACE_RGBA = 'rgba';

    public function __construct(Application $app, array $options)
    {
        if (!isset($options['colorspaces'])) {
            throw new \InvalidArgumentException('Missing "colorspaces" options');
        }

        $this->colorspaces = array_map('strtolower', (array) $options['colorspaces']);
        parent::__construct($app);
    }

    public function check(EntityManager $em, File $file)
    {
        $boolean = false;

        if (0 === count($this->colorspaces)) {
            $boolean = true; //bypass color if empty array
        } elseif ($file->getMedia()->getType() === Document::TYPE_DOCUMENT) {
            $boolean = true; //bypass color checker if file is of type document
        } elseif (method_exists($file->getMedia(), 'getColorSpace')) {
            $colorspace = null;
            switch ($file->getMedia()->getColorSpace())
            {
                case \MediaVorus\Media\Image::COLORSPACE_CMYK:
                    $colorspace = self::COLORSPACE_CMYK;
                    break;
                case \MediaVorus\Media\Image::COLORSPACE_RGB:
                case \MediaVorus\Media\Image::COLORSPACE_SRGB:
                    $colorspace = self::COLORSPACE_RGB;
                    break;
                case \MediaVorus\Media\Image::COLORSPACE_GRAYSCALE:
                    $colorspace = self::COLORSPACE_GRAYSCALE;
                    break;
                case \MediaVorus\Media\Image::COLORSPACE_RGBA:
                    $colorspace = self::COLORSPACE_RGBA;
                    break;
            }

            $boolean = $colorspace !== null && in_array(strtolower($colorspace), $this->colorspaces);
        }

        return new Response($boolean, $this);
    }

    public function getMessage(TranslatorInterface $translator)
    {
        return $translator->trans('The file does not match available color');
    }
}
