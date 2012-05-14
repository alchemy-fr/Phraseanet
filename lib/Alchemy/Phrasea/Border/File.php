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

use MediaVorus\Media\Media;
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
     * @param Media         $media          The media
     * @param \collection   $collection     The destination collection
     * @param string        $originalName   The original name of the file
     *                                      (if not provided, original name is
     *                                      extracted from the pathfile)
     */
    public function __construct(Media $media, \collection $collection, $originalName = null)
    {
        $this->media = $media;
        $this->collection = $collection;
        $this->attributes = array();
        $this->originalName = $originalName ? : pathinfo($this->media->getFile()->getPathname(), PATHINFO_BASENAME);
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
     * Checks for UUID in metadatas, If not create it and write it.
     * Be carefull, this methods writes in file.
     *
     * The unique Id is first read in document metadatas. If not found, it is
     * generated
     *
     * @todo Check if a file exists with the same checksum
     * @todo Check if an UUID is contained in the attributes, replace It if
     *              necessary
     *
     * @return \Alchemy\Phrasea\Border\File
     */
    public function getUUID($generate = false, $write = false)
    {
        if ($this->uuid && ! $write) {
            return $this->uuid;
        }

        if ( ! $this->uuid) {
            $metadatas = $this->media->getEntity()->getMetadatas();

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

            if ( ! $uuid && $generate) {
                /**
                 * @todo Check if a file exists with the same checksum
                 */
                $uuid = \uuid::generate_v4();
            }

            $this->uuid = $uuid;
        }

        if ($write) {
            $writer = new Writer();

            $value = new MonoValue($uuid);
            $metadatas = new MetadataBag();

            foreach ($available as $tagname) {
                $metadatas->add(new Metadata(TagFactory::getFromRDFTagname($tagname), $value));
            }

            $writer->write($this->getFile()->getRealPath(), $metadatas);
        }

        $writer = $reader = $metadatas = null;

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
            $this->sha256 = $this->media->getHash('sha256');
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
            $this->md5 = $this->media->getHash('md5');
        }

        return $this->md5;
    }

    /**
     * Returns the SplFileInfo related to the document
     *
     * @return \SplFileInfo
     */
    public function getFile()
    {
        return $this->media->getFile();
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
     * Build the File package object
     *
     * @param string        $pathfile       The path to the file
     * @param \collection   $collection     The destination collection
     * @param string        $originalName   An optionnal original name (if
     *                                      different from the $pathfile filename)
     * @throws \InvalidArgumentException
     *
     * @return \Alchemy\Phrasea\Border\File
     */
    public function buildFromPathfile($pathfile, \collection $collection, $originalName = null)
    {
        try {
            $media = MediaVorus::guess(new \SplFileInfo($pathfile));
        } catch (\MediaVorus\Exception\FileNotFoundException $e) {
            throw new \InvalidArgumentException(sprintf('Unable to build media file from non existant %s', $pathfile));
        }

        return new File($media, $collection, $originalName);
    }
}
