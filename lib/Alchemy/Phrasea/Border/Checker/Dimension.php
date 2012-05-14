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

class Dimension implements Checker
{
    protected $width;
    protected $height;

    public function __construct($width, $height = null)
    {
        if ($height === null) {
            $height = $width;
        }

        if ((int) $height <= 0 || (int) $width <= 0) {
            throw new \InvalidArgumentException('Dimensions should be greater than 0');
        }

        $this->width = $width;
        $this->height = $height;
    }

    public function check(EntityManager $em, File $file)
    {
        $boolean = false;

        if (method_exists($file->getMedia(), 'getWidth')) {

            $boolean = $file->getMedia()->getWidth() >= $this->width
                && $file->getMedia()->getHeight() >= $this->height;
        }

        return new Response($boolean, $this);
    }

    public static function getMessage()
    {
        return _('The file does not match required dimension');
    }
}
