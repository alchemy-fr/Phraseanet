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

class Colorspace implements Checker
{
    protected $colorspaces;

    public function __construct(array $colorspaces)
    {
        $this->colorspaces = array_map('strtolower', $colorspaces);
    }

    public function check(EntityManager $em, File $file)
    {
        $boolean = false;

        if (method_exists($file->getMedia(), 'getColorSpace')) {
            $boolean = in_array(strtolower($file->getMedia()->getColorSpace()), $this->colorspaces);
        }

        return new Response($boolean, $this);
    }

    public static function getMessage()
    {
        return _('The file does not match available color');
    }
}
