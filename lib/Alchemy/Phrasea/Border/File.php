<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Media\Type\Audio;
use Alchemy\Phrasea\Media\Type\Document;
use Alchemy\Phrasea\Media\Type\Flash;
use Alchemy\Phrasea\Media\Type\Image;
use Alchemy\Phrasea\Media\Type\Video;
use Alchemy\Phrasea\MediaVorus\Exception\FileNotFoundException;
use Alchemy\Phrasea\MediaVorus\Media\MediaInterface;
use Alchemy\Phrasea\Metadata\TagFactory;
use Alchemy\Phrasea\PHPExiftool\Driver\Metadata\Metadata;
use Alchemy\Phrasea\PHPExiftool\Driver\Metadata\MetadataBag as ExiftoolMetadataBag;
use Alchemy\Phrasea\PHPExiftool\Driver\Value\Mono as MonoValue;
use Alchemy\Phrasea\PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use Ramsey\Uuid\Uuid;

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
     * @var MediaInterface
     */
    protected $media;
    protected $uuid;
    protected $sha256;
    protected $app;
    protected $originalName;
    protected $md5;
    protected $attributes;
    public static $xmpTag = ['XMP-xmpMM:DocumentID'];

    /**
     * Constructor
     *
     * @param Application    $app          Application context
     * @param MediaInterface $media        The media
     * @param \collection    $collection   The destination collection
     * @param string         $originalName The original name of the file
     *                                     (if not provided, original name is
     *                                     extracted from the pathfile)
     */
    public function __construct(Application $app, MediaInterface $media, \collection $collection, $originalName = null)
    {
        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("into construct")
        ), FILE_APPEND | LOCK_EX);

        $this->app = $app;
        $this->media = $media;
        $this->collection = $collection;
        $this->attributes = [];
        $this->originalName = $originalName ?: pathinfo($this->media->getFile()->getPathname(), PATHINFO_BASENAME);
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
     * Checks for UUID in metadatas
     *
     * @param  boolean $generate if true, if no uuid found, a valid one is generated
     * @param  boolean $write    if true, writes uuid in all available metadatas
     * @return string
     */
    public function getUUID($generate = false, $write = false)
    {
        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("into getuuid for \"%s\" with generate=%s, write=%s ; this->uuid=%s", $this->getFile()->getRealPath(), $generate?'true':'false', $write?'true':'false', $this->uuid ?:'null')
        ), FILE_APPEND | LOCK_EX);

        if ($this->uuid && !$write) {
            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("return known uuid %s", $this->uuid)
            ), FILE_APPEND | LOCK_EX);

            return $this->uuid;
        }

        $availableUUIDs = [
            'XMP-exif:ImageUniqueID',
            'SigmaRaw:ImageUniqueID',
            'IPTC:UniqueDocumentID',
            'ExifIFD:ImageUniqueID',
            'Canon:ImageUniqueID',
            'XMP-xmpMM:DocumentID',
        ];

        if (!$this->uuid) {
            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("getMetadatas to get uuid")
            ), FILE_APPEND | LOCK_EX);

            $metadatas = $this->media->getMetadatas();

            $uuid = null;

            foreach ($availableUUIDs as $meta) {
                if ($metadatas->containsKey($meta)) {
                    $candidate = $metadatas->get($meta)->getValue()->asString();
                    if(in_array($meta, self::$xmpTag)){
                        $candidate = self::sanitizeXmpUuid($candidate);
                    }
                    if (Uuid::isValid($candidate)) {
                        $uuid = $candidate;

                        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                            sprintf("found uuid from %s ; %s", $meta, $uuid)
                        ), FILE_APPEND | LOCK_EX);

                        break;
                    }
                }
            }

            if (!$uuid && $generate) {
                $uuid = Uuid::uuid4();
                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("generated uuid %s", $uuid)
                ), FILE_APPEND | LOCK_EX);
            }

            $this->uuid = $uuid;
        }

        if ($write) {
            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("writing uuid %s", $this->uuid)
            ), FILE_APPEND | LOCK_EX);

            $value = new MonoValue($this->uuid);
            $metadatas = new ExiftoolMetadataBag();

            foreach ($availableUUIDs as $tagname) {
                $metadatas->add(new Metadata(TagFactory::getFromRDFTagname($tagname), $value));
            }

            try {
                $writer = $this->app['exiftool.writer'];
                $writer->reset();

                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("exiftool will write to %s", $this->getFile()->getRealPath())
                ), FILE_APPEND | LOCK_EX);

                $writer->write($this->getFile()->getRealPath(), $metadatas);

                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("exiftool has writen ok to %s", $this->getFile()->getRealPath())
                ), FILE_APPEND | LOCK_EX);

            } catch (PHPExiftoolException $e) {

                file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                    sprintf("exiftool failed to write to %s because (%s)", $this->getFile()->getRealPath(), $e->getMessage())
                ), FILE_APPEND | LOCK_EX);

                // PHPExiftool throws exception on some files not supported
            }
        }

        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return uuid %s", $this->uuid ?: 'null')
        ), FILE_APPEND | LOCK_EX);

        return $this->uuid;
    }

    /**
     *
     * @return \Alchemy\Phrasea\Media\Type\Type|null
     */
    public function getType()
    {
        switch ($this->media->getType())
        {
            case MediaInterface::TYPE_AUDIO:
                return new Audio();
                break;
            case MediaInterface::TYPE_DOCUMENT:
                return new Document();
                break;
            case MediaInterface::TYPE_FLASH:
                return new Flash();
                break;
            case MediaInterface::TYPE_IMAGE:
                return new Image();
                break;
            case MediaInterface::TYPE_VIDEO:
                return new Video();
                break;
        }

        return null;
    }

    /**
     * Returns the sha256 checksum of the document
     *
     * @return string
     */
    public function getSha256()
    {
        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("into getSha256() for \"%s\" ; this->sha256=%s", $this->getFile()->getRealPath(), $this->sha256 ?: 'null')
        ), FILE_APPEND | LOCK_EX);

        if (!$this->sha256) {
            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("sha256 unknown, calling mediavorus::getHash('sha256')")
            ), FILE_APPEND | LOCK_EX);

            $this->sha256 = $this->media->getHash('sha256');

            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("mediavorus::getHash('sha256') returned \"%s\"", $this->sha256)
            ), FILE_APPEND | LOCK_EX);
        }
        else {
            file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
                sprintf("sha256 known (\"%s\")", $this->sha256)
            ), FILE_APPEND | LOCK_EX);
        }

        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return sha256 %s", $this->sha256 ?: 'null')
        ), FILE_APPEND | LOCK_EX);

        return $this->sha256;
    }

    /**
     * Returns the md5 checksum of the document
     *
     * @return string
     */
    public function getMD5()
    {
        if (!$this->md5) {
            $this->md5 = $this->media->getHash('md5');
        }

        return $this->md5;
    }

    /**
     * Returns the MediaVorus File related to the document
     *
     * @return \MediaVorus\File
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
     * Returns an instance of MediaVorus\Media\MediaInterface corresponding to the file
     *
     * @return MediaInterface
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
     * Returns an array of AttributeInterface associated to the file
     *
     * @return AttributeInterface[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Adds an attribute to the file package
     *
     * @param  AttributeInterface $attribute The attribute
     * @return File
     */
    public function addAttribute(AttributeInterface $attribute)
    {
        array_push($this->attributes, $attribute);

        return $this;
    }

    /**
     * Build the File package object
     *
     * @param  string                    $pathfile     The path to the file
     * @param  \collection               $collection   The destination collection
     * @param  Application               $app          An application
     * @param  string                    $originalName An optionnal original name (if
     *                                                 different from the $pathfile filename)
     * @throws \InvalidArgumentException
     *
     * @return File
     */
    public static function buildFromPathfile($pathfile, \collection $collection, Application $app, $originalName = null)
    {
        try {
            $media = $app->getMediaFromUri($pathfile);
        } catch (FileNotFoundException $e) {
            throw new \InvalidArgumentException(sprintf('Unable to build media file from non existant %s', $pathfile));
        }

        return new File($app, $media, $collection, $originalName);
    }

    /**
     * Sanitize XMP UUID
     * @param $uuid
     * @return mixed
     */
    public static function sanitizeXmpUuid($uuid){
        return str_replace('xmp.did:', '', $uuid);
    }
}
