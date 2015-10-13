<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Metadata;

use PHPExiftool\Driver\TagFactory as BaseTagFactory;
use PHPExiftool\Exception\TagUnknown;

class TagFactory extends BaseTagFactory
{
    protected static $knownClasses = [
        'pdftext'       => 'Alchemy\Phrasea\Metadata\Tag\PdfText',
        'tfarchivedate' => 'Alchemy\Phrasea\Metadata\Tag\TfArchivedate',
        'tfatime'       => 'Alchemy\Phrasea\Metadata\Tag\TfAtime',
        'tfbasename'    => 'Alchemy\Phrasea\Metadata\Tag\TfBasename',
        'tfbits'        => 'Alchemy\Phrasea\Metadata\Tag\TfBits',
        'tfchannels'    => 'Alchemy\Phrasea\Metadata\Tag\TfChannels',
        'tfctime'       => 'Alchemy\Phrasea\Metadata\Tag\TfCtime',
        'tfdirname'     => 'Alchemy\Phrasea\Metadata\Tag\TfDirname',
        'tfduration'    => 'Alchemy\Phrasea\Metadata\Tag\TfDuration',
        'tfeditdate'    => 'Alchemy\Phrasea\Metadata\Tag\TfEditdate',
        'tfextension'   => 'Alchemy\Phrasea\Metadata\Tag\TfExtension',
        'tffilename'    => 'Alchemy\Phrasea\Metadata\Tag\TfFilename',
        'tffilepath'    => 'Alchemy\Phrasea\Metadata\Tag\TfFilepath',
        'tfheight'      => 'Alchemy\Phrasea\Metadata\Tag\TfHeight',
        'tfmimetype'    => 'Alchemy\Phrasea\Metadata\Tag\TfMimetype',
        'tfmtime'       => 'Alchemy\Phrasea\Metadata\Tag\TfMtime',
        'tfquarantine'  => 'Alchemy\Phrasea\Metadata\Tag\TfQuarantine',
        'tfrecordid'    => 'Alchemy\Phrasea\Metadata\Tag\TfRecordid',
        'tfsize'        => 'Alchemy\Phrasea\Metadata\Tag\TfSize',
        'tfwidth'       => 'Alchemy\Phrasea\Metadata\Tag\TfWidth',
    ];

    public static function getFromTagname($tagname)
    {
        $classname = static::classnameFromTagname($tagname);

        if ( ! class_exists($classname)) {
            throw new TagUnknown(sprintf('Unknown tag %s', $tagname));
        }

        return new $classname;
    }

    protected static function classnameFromTagname($tagname)
    {
        $tagname = str_replace('rdf:RDF/rdf:Description/', '', $tagname);

        $parts = explode(':', strtolower($tagname), 2);
        if (count($parts) == 2 && $parts[0] == 'phraseanet' && isset(self::$knownClasses[$parts[1]])) {
            // a specific phraseanet fieldname
            return self::$knownClasses[$parts[1]];
        }
        // another (exiftool) fieldname ?
        return parent::classnameFromTagname($tagname);
    }
}
