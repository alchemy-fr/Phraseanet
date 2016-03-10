<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
        'pdf-text' => Tag\PdfText::class,
        'no-source' => Tag\NoSource::class,
        'tf-archivedate' => Tag\TfArchivedate::class,
        'tf-atime' => Tag\TfAtime::class,
        'tf-basename' => Tag\TfBasename::class,
        'tf-bits' => Tag\TfBits::class,
        'tf-channels' => Tag\TfChannels::class,
        'tf-ctime' => Tag\TfCtime::class,
        'tf-dirname' => Tag\TfDirname::class,
        'tf-duration' => Tag\TfDuration::class,
        'tf-editdate' => Tag\TfEditdate::class,
        'tf-extension' => Tag\TfExtension::class,
        'tf-filename' => Tag\TfFilename::class,
        'tf-filepath' => Tag\TfFilepath::class,
        'tf-height' => Tag\TfHeight::class,
        'tf-mimetype' => Tag\TfMimetype::class,
        'tf-mtime' => Tag\TfMtime::class,
        'tf-quarantine' => Tag\TfQuarantine::class,
        'tf-recordid' => Tag\TfRecordid::class,
        'tf-size' => Tag\TfSize::class,
        'tf-width' => Tag\TfWidth::class,
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
