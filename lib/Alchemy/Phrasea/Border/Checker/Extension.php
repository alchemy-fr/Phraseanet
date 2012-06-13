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

class Extension implements Checker
{
    protected $extensions;

    public function __construct(array $options)
    {
        if ( ! isset($options['extensions'])) {
            throw new \InvalidArgumentException('Missing "extensions" options');
        }

        $this->extensions = array_map('strtolower', (array) $options['extensions']);
    }

    public function check(EntityManager $em, File $file)
    {
        if (0 === count($this->extensions)) { //if empty authorize all extensions
            $boolean = true;
        } else {
            $boolean = in_array(strtolower($file->getFile()->getExtension()), $this->extensions);
        }

        return new Response($boolean, $this);
    }

    public static function getMessage()
    {
        return _('The file does not match available extensions');
    }
}
