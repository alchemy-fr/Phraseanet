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
        'pdf-text'       => 'Alchemy\Phrasea\Metadata\Tag\PdfText',
        'tf-archivedate' => 'Alchemy\Phrasea\Metadata\Tag\TfArchivedate',
        'tf-atime'       => 'Alchemy\Phrasea\Metadata\Tag\TfAtime',
        'tf-basename'    => 'Alchemy\Phrasea\Metadata\Tag\TfBasename',
        'tf-bits'        => 'Alchemy\Phrasea\Metadata\Tag\TfBits',
        'tf-channels'    => 'Alchemy\Phrasea\Metadata\Tag\TfChannels',
        'tf-ctime'       => 'Alchemy\Phrasea\Metadata\Tag\TfCtime',
        'tf-dirname'     => 'Alchemy\Phrasea\Metadata\Tag\TfDirname',
        'tf-duration'    => 'Alchemy\Phrasea\Metadata\Tag\TfDuration',
        'tf-editdate'    => 'Alchemy\Phrasea\Metadata\Tag\TfEditdate',
        'tf-extension'   => 'Alchemy\Phrasea\Metadata\Tag\TfExtension',
        'tf-filename'    => 'Alchemy\Phrasea\Metadata\Tag\TfFilename',
        'tf-filepath'    => 'Alchemy\Phrasea\Metadata\Tag\TfFilepath',
        'tf-height'      => 'Alchemy\Phrasea\Metadata\Tag\TfHeight',
        'tf-mimetype'    => 'Alchemy\Phrasea\Metadata\Tag\TfMimetype',
        'tf-mtime'       => 'Alchemy\Phrasea\Metadata\Tag\TfMtime',
        'tf-quarantine'  => 'Alchemy\Phrasea\Metadata\Tag\TfQuarantine',
        'tf-recordid'    => 'Alchemy\Phrasea\Metadata\Tag\TfRecordid',
        'tf-size'        => 'Alchemy\Phrasea\Metadata\Tag\TfSize',
        'tf-width'       => 'Alchemy\Phrasea\Metadata\Tag\TfWidth',
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
