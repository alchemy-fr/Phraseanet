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

use Alchemy\Phrasea\Metadata\Tag\ExiftoolTag;
use PHPExiftool\Driver\HelperInterface;
use PHPExiftool\Driver\TagGroupFactory;
use PHPExiftool\Exception\TagUnknown;

class TagFactory
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

    /** @var string|callable|null */
    private static $classesDirectory;

    /**
     * Sets the phpexiftool generated classes directory, or a callable returning it (resolved on first use).
     *
     * The tag factory is static, so this directory is process wide.
     *
     * @param string|callable|null $classesDirectory
     */
    public static function setClassesDirectory($classesDirectory)
    {
        self::$classesDirectory = $classesDirectory;
    }

    /**
     * @return string
     */
    public static function getClassesDirectory()
    {
        if (is_callable(self::$classesDirectory)) {
            self::$classesDirectory = call_user_func(self::$classesDirectory);
        }

        if (!is_string(self::$classesDirectory) || '' === self::$classesDirectory) {
            throw new \LogicException('The phpexiftool classes directory is not configured');
        }

        return self::$classesDirectory;
    }

    /**
     * @param string $tagname A tag name ("IPTC:Keywords", "Phraseanet:tf-filename"), optionally prefixed by "/rdf:RDF/rdf:Description/"
     * @return TagInterface
     * @throws TagUnknown
     */
    public static function getFromRDFTagname($tagname)
    {
        $tagname = self::normalizeTagname($tagname);

        if (null !== $classname = self::getPhraseanetClassname($tagname)) {
            return new $classname();
        }

        return new ExiftoolTag(TagGroupFactory::getFromRDFTagname(self::getClassesDirectory(), $tagname));
    }

    /**
     * @param string $tagname
     * @return bool
     */
    public static function hasFromRDFTagname($tagname)
    {
        $tagname = self::normalizeTagname($tagname);

        return null !== self::getPhraseanetClassname($tagname)
            || TagGroupFactory::hasFromRDFTagname(self::getClassesDirectory(), $tagname);
    }

    /**
     * @return string[] The Phraseanet tag classes, indexed by lowercased tag name (without the "Phraseanet:" group)
     */
    public static function getPhraseanetClasses()
    {
        return self::$knownClasses;
    }

    /**
     * @return string[] The exiftool tag group class names (relative to the generated namespace), indexed by tag name
     */
    public static function getExiftoolIndex()
    {
        /** @var HelperInterface $helper */
        $helper = TagGroupFactory::loadClass(self::getClassesDirectory(), 'TagGroup\\Helper');

        return $helper::getIndex();
    }

    private static function normalizeTagname($tagname)
    {
        return str_ireplace(['/rdf:RDF/rdf:Description/', 'rdf:RDF/rdf:Description/'], '', (string) $tagname);
    }

    private static function getPhraseanetClassname($tagname)
    {
        $parts = explode(':', strtolower($tagname), 2);

        if (count($parts) == 2 && $parts[0] == 'phraseanet' && isset(self::$knownClasses[$parts[1]])) {
            return self::$knownClasses[$parts[1]];
        }

        return null;
    }
}
