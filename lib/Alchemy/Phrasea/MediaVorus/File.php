<?php

/*
 * This file is part of MediaVorus.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\MediaVorus;

use Alchemy\Phrasea\MediaVorus\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException as SFFileNotFoundException;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class File extends SymfonyFile
{

    public function __construct($path)
    {
        try {
            parent::__construct($path, true);
        } catch (SFFileNotFoundException $e) {
            throw new FileNotFoundException(sprintf('File %s not found', $path));
        }
    }

}
