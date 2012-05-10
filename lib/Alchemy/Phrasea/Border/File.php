<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use MediaVorus\MediaVorus;
use PHPExiftool\Reader;
use PHPExiftool\Writer;
use PHPExiftool\Driver\TagFactory;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;
use PHPExiftool\Driver\Value\Mono as MonoValue;

/**
 * Phraseanet candidate File package
 *
 * To submit a file against Phraseanet Border constraits, the file must be
 * packaged with this class.
 *
 */
class File
{
    protected $collection;

    /**
     *
     * @var \MediaVorus\Media\Media
     */
    protected $media;
    protected $uuid;
    protected $sha256;
    protected $originalName;
    protected $md5;
    protected $attributes;

    /**
     * Constructor
     *
     * @param type          $pathfile       The path to the file
     * @param \collection   $collection     The destination collection
     * @param type          $originalName   The original name of the file
     *                                      (if not provided, original name is
     *                                      extracted from the pathfile)
     */
    public function __construct($pathfile, \collection $collection, $originalName = null)
    {
        $this->media = MediaVorus::guess(new \SplFileInfo($pathfile));
        $this->collection = $collection;
        $this->attributes = array();
        $this->originalName = $originalName ? : pathinfo($pathfile, PATHINFO_BASENAME);

        $this->ensureUUID();
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        $this->collection = null;
        $this->media = null;
    }

    /**
     * Returns the document Unique ID
     *
     * @return string
     */
    public function getUUID()
    {
        return $this->uuid;
    }

    /**
     * Returns the sha256 checksum of the document
     *
     * @return string
     */
    public function getSha256()
    {
        if ( ! $this->sha256) {
            $this->sha256 = hash_file('sha256', $this->getPathfile());
        }

        return $this->sha256;
    }

    /**
     * Returns the md5 checksum of the document
     *
     * @return string
     */
    public function getMD5()
    {
        if ( ! $this->md5) {
            $this->md5 = hash_file('md5', $this->getPathfile());
        }

        return $this->md5;
    }

    /**
     * Returns the realpath to the document
     *
     * @return string
     */
    public function getPathfile()
    {
        return $this->media->getFile()->getRealpath();
    }

    /**
     * Returns the original name of the document
     *
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Returns an instance of MediaVorus\Media\Media corresponding to the file
     *
     * @return MediaVorus\Media\Media
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Returns the destination collection for the file
     *
     * @return \collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Returns an array of Attribute\Attribute associated to the file
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Adds an attribute to the file package
     *
     * @param   Attribute\Attribute $attribute  The attribute
     * @return  File
     */
    public function addAttribute(Attribute\Attribute $attribute)
    {
        array_push($this->attributes, $attribute);

        return $this;
    }

    /**
     * Checks for UUID in metadatas
     *
     * The unique Id is first read in document metadatas. If not found, it is
     * generated
     *
     * @todo Check if an UUID is contained in the attributes
     *
     * @return \Alchemy\Phrasea\Border\File
     */
    protected function ensureUUID()
    {
        $reader = new Reader();
        $metadatas = $reader->files($this->getPathfile())->first()->getMetadatas();

        $available = array(
            'XMP-exif:ImageUniqueID',
            'SigmaRaw:ImageUniqueID',
            'IPTC:UniqueDocumentID',
            'ExifIFD:ImageUniqueID',
            'Canon:ImageUniqueID',
        );

        $uuid = null;

        foreach ($available as $meta) {
            if ($metadatas->containsKey($meta)) {
                $candidate = $metadatas->get($meta)->getValue()->asString();
                if (\uuid::is_valid($candidate)) {
                    $uuid = $candidate;
                    break;
                }
            }
        }

        if ( ! $uuid) {
            $uuid = \uuid::generate_v4();
        }

        $this->uuid = $uuid;

        $writer = new Writer();

        $value = new MonoValue($uuid);
        $metadatas = new MetadataBag();

        foreach ($available as $tagname) {
            $metadatas->add(new Metadata(TagFactory::getFromRDFTagname($tagname), $value));
        }

        $writer->write($this->getPathfile(), $metadatas);

        $writer = $reader = $metadatas = null;

        return $this;
    }
}
