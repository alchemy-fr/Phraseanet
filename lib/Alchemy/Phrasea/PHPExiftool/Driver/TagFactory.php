<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver;

use Alchemy\Phrasea\PHPExiftool\Exception\TagUnknown;
use Alchemy\Phrasea\PHPExiftool\Tool\Command\ClassesBuilder;

/**
 * Metadata Object for mapping a Tag to a value
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class TagFactory
{

    /**
     * Build a Tag based on his Tagname
     *
     * @param  string       $tagname
     * @return TagInterface
     * @throws TagUnknown
     */
    public static function getFromRDFTagname($tagname)
    {
        $classname = static::classnameFromTagname($tagname);

        if ( ! class_exists($classname)) {
            throw new TagUnknown(sprintf('Unknown tag %s', $tagname));
        }

        return new $classname;
    }

    protected static function classnameFromTagname($tagname)
    {
        $tagname = str_replace('/rdf:RDF/rdf:Description/', '', $tagname);

        $classname = '\Alchemy\Phrasea\PHPExiftool\Driver\Tag\\' . str_replace(':', '\\', $tagname);

        return ClassesBuilder::generateNamespace($classname);
    }

    public static function hasFromRDFTagname($tagname)
    {
        return class_exists(static::classnameFromTagname($tagname));
    }
}
