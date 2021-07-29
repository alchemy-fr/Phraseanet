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
use Alchemy\Phrasea\MediaVorus\Media\MediaInterface;
use Alchemy\Phrasea\PHPExiftool\Reader;
use Alchemy\Phrasea\PHPExiftool\Writer;
use FFMpeg\FFProbe;
use Monolog\Handler\NullHandler;
use Monolog\Logger;

/**
 *
 * @author      Romain Neutron - imprec@gmail.com
 * @license     http://opensource.org/licenses/MIT MIT
 */
class MediaVorus
{
    private $reader;
    private $writer;
    private $ffprobe;

    private static $guess_cache = [];

    public function __construct(Reader $reader, Writer $writer, FFProbe $ffprobe = null)
    {
        $this->reader = $reader;
        $this->writer = $writer;
        $this->ffprobe = $ffprobe;
    }

    /**
     * Create MediaVorus
     *
     * @return MediaVorus
     */
    public static function create()
    {
        $logger = new Logger('MediaVorus');
        $logger->pushHandler(new NullHandler());

        return new static(Reader::create($logger), Writer::create($logger), FFProbe::create(array(), $logger));
    }

    /**
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * @return Writer
     */
    public function getWriter()
    {
        return $this->writer;
    }

    /**
     * @return FFProbe
     */
    public function getFFProbe()
    {
        return $this->ffprobe;
    }

    /**
     * invalidate the "guess" cache for a file (or for all files if "file" arg is null)
     *
     * @param string|null $file
     */
    public function clearGuessCache($file = null)
    {
        $old = self::$guess_cache;
        self::$guess_cache = [];
        foreach($old as $entry) {
            if($file && $entry['file'] != $file) {
                self::$guess_cache[] = $entry;
            }
        }
    }

    /**
     * Build a Media Object given a file
     *
     * @param string $file
     * @return MediaInterface
     * @throws FileNotFoundException
     */
    public function guess($file)
    {
        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("into guess(\"%s\")", $file)
        ), FILE_APPEND | LOCK_EX);

        // keep the last 4 files in cache (but 2 should be enough)
        $found = null;
        foreach(self::$guess_cache as $entry) {
            if($entry['file'] === $file) {
                $found = $entry['media'];
                break;
            }
        }
        if(!$found) {
            // not in cache ? we need to call new media() which is expensive
            if(count(self::$guess_cache) == 4) {
                // keep a max of 4 results in cache
                array_shift(self::$guess_cache);
            }
            $fileObj = new File($file);
            $classname = "Alchemy\\Phrasea\\" . $this->guessFromMimeType($fileObj->getMimeType());

            // save in cache
            self::$guess_cache[] = [
                'file'  => $file,
                'media' => ($found = new $classname($fileObj, $this->reader->reset()->files($file)->first(), $this->writer, $this->ffprobe))
                ];
        }

        file_put_contents(dirname(__FILE__).'/../../../../logs/trace.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from guess(...)")
        ), FILE_APPEND | LOCK_EX);

        /** @var  \Alchemy\Phrasea\MediaVorus\Media\MediaInterface $found */
        /** @var  \Alchemy\Phrasea\MediaVorus\Media\DefaultMedia $found */
        return $found;
    }

    /**
     * Return the corresponding \MediaVorus\Media\* class corresponding to a
     * mimetype
     *
     * @param string $mime
     * @return string The name of the MediaType class to use
     */
    protected function guessFromMimeType($mime)
    {
        $mime = strtolower($mime);

        switch (true) {
            case strpos($mime, 'image/') === 0:
            case $mime === 'application/postscript':
            case $mime === 'application/illustrator':
                return 'MediaVorus\Media\Image';
                break;

            case strpos($mime, 'video/') === 0:
            case $mime === 'application/vnd.rn-realmedia':
            case $mime === 'application/mxf':
                return 'MediaVorus\Media\Video';
                break;

            case strpos($mime, 'audio/') === 0:
                return 'MediaVorus\Media\Audio';
                break;

            /**
             * @todo Implements Documents
             */
            case strpos($mime, 'text/') === 0:
            case $mime === 'application/msword':
            case $mime === 'application/access':
            case $mime === 'application/pdf':
            case $mime === 'application/excel':
            case $mime === 'application/powerpoint':
            case $mime === 'application/vnd.ms-powerpoint':
            case $mime === 'application/vnd.ms-excel':
            case $mime === 'application/vnd.ms-office':
            case $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
            case $mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.template':
            case $mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
            case $mime === 'application/vnd.openxmlformats-officedocument.spreadsheetml.template':
            case $mime === 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
            case $mime === 'application/vnd.openxmlformats-officedocument.presentationml.template':
            case $mime === 'application/vnd.openxmlformats-officedocument.presentationml.slideshow':
            case $mime === 'application/vnd.oasis.opendocument.formula':
            case $mime === 'application/vnd.oasis.opendocument.text-master':
            case $mime === 'application/vnd.oasis.opendocument.database':
            case $mime === 'application/vnd.oasis.opendocument.formula':
            case $mime === 'application/vnd.oasis.opendocument.chart':
            case $mime === 'application/vnd.oasis.opendocument.graphics':
            case $mime === 'application/vnd.oasis.opendocument.presentation':
            case $mime === 'application/vnd.oasis.opendocument.spreadsheet':
            case $mime === 'application/vnd.oasis.opendocument.text':
            case $mime === 'application/x-indesign':
                return 'MediaVorus\Media\Document';
                break;

            case $mime === 'application/x-shockwave-flash':
                return 'MediaVorus\Media\Flash';
                break;

            default:
                break;
        }

        return 'MediaVorus\Media\DefaultMedia';
    }

    /**
     *
     * @param \SplFileInfo $dir
     * @param bool $recursive
     *
     * @return MediaCollection
     */
    public function inspectDirectory($dir, $recursive = false)
    {
        $this->reader
            ->reset()
            ->in($dir)
            ->followSymLinks();

        if ( ! $recursive) {
            $this->reader->notRecursive();
        }

        $files = new MediaCollection();

        foreach ($this->reader as $entity) {
            $file = new File($entity->getFile());
            $classname = $this->guessFromMimeType($file->getMimeType());
            $files[] = new $classname($file, $entity, $this->writer, $this->ffprobe);
        }

        return $files;
    }
}
