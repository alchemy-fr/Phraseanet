<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst;

use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Exception\ExecutableNotFoundException as FFMpegExecutableNotFound;
use Ghostscript\Transcoder;
use MediaVorus\MediaVorus;
use MediaAlchemyst\Exception\RuntimeException;
use MediaAlchemyst\Exception\InvalidArgumentException;
use MP4Box\MP4Box;
use PHPExiftool\Exception\ExceptionInterface as PHPExiftoolException;
use PHPExiftool\Exiftool;
use PHPExiftool\InformationDumper;
use PHPExiftool\PHPExiftool;
use PHPExiftool\PreviewExtractor;
use PHPExiftool\Reader;
use PHPExiftool\RDFParser;
use PHPExiftool\Writer;
use Pimple\Container;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Unoconv\Unoconv;

class DriversContainer extends Container
{
    public function __construct(array $values = array())
    {
        parent::__construct();

        $this['logger'] = function (Container $container) {
            return new NullLogger();
        };

        $this['default.configuration'] = array(
            'ffmpeg.threads'               => 4,
            'ffmpeg.ffmpeg.timeout'        => 3600,
            'ffmpeg.ffprobe.timeout'       => 60,
            'ffmpeg.ffmpeg.binaries'       => null,
            'ffmpeg.ffprobe.binaries'      => null,
            'imagine.driver'               => null,
            'gs.timeout'                   => 60,
            'gs.binaries'                  => null,
            'mp4box.timeout'               => 60,
            'mp4box.binaries'              => null,
            'unoconv.binaries'             => null,
            'unoconv.timeout'              => 60,
            'unoconv.port'                 => null,
            'exiftool.binary'              => null,
            'exiftool.classes-directory'   => null,
        );
        $this['configuration'] = array();
        $this['configuration.merged'] = function (Container $container) {
            return array_replace(
                $container['default.configuration'], $container['configuration']
            );
        };
        $this['ffmpeg.ffmpeg'] = function (Container $container) {
            try {
                return FFMpeg::create(array_filter(array(
                    'ffmpeg.threads'  => $container['configuration.merged']['ffmpeg.threads'],
                    'timeout'         => $container['configuration.merged']['ffmpeg.ffmpeg.timeout'],
                    'ffmpeg.binaries' => $container['configuration.merged']['ffmpeg.ffmpeg.binaries'],
                )), $container['logger'], $container['ffmpeg.ffprobe']);
            } catch (FFMpegExecutableNotFound $e) {
                throw new RuntimeException('Unable to create FFMpeg driver', $e->getCode(), $e);
            }
        };
        $this['ffmpeg.ffprobe.cache'] = function (Container $container) {
            return new ArrayAdapter();
        };
        $this['ffmpeg.ffprobe'] = function (Container $container) {
            try {
                return FFProbe::create(array_filter(array(
                    'timeout'          => $container['configuration.merged']['ffmpeg.ffprobe.timeout'],
                    'ffprobe.binaries' => $container['configuration.merged']['ffmpeg.ffprobe.binaries'],
                )), $container['logger'], $container['ffmpeg.ffprobe.cache']);
            } catch (FFMpegExecutableNotFound $e) {
                throw new RuntimeException('Unable to create FFProbe driver', $e->getCode(), $e);
            }
        };
        $this['imagine'] = function (Container $container) {
            $driver = $container['configuration.merged']['imagine.driver'];

            switch (true) {
                case 'imagick' === strtolower((string) $driver):
                case null === $driver && class_exists('Imagick'):
                    $driver = 'Imagine\Imagick\Imagine';
                    break;
                case 'gmagick' === strtolower((string) $driver):
                case null === $driver && class_exists('Gmagick'):
                    $driver = 'Imagine\Gmagick\Imagine';
                    break;
                case 'gd' === strtolower((string) $driver):
                case null === $driver && extension_loaded('gd'):
                    $driver = 'Imagine\Gd\Imagine';
                    break;
            }

            if (null === $driver || false === class_exists($driver) || false === in_array('Imagine\Image\ImagineInterface', class_implements($driver))) {
                throw new InvalidArgumentException(sprintf('Invalid Imagine driver %s', $driver));
            }

            return new $driver();
        };

        $this['unoconv'] = function (Container $container) {
            try {
                return Unoconv::create(array_filter(array(
                    'unoconv.binaries' => $container['configuration.merged']['unoconv.binaries'],
                    'timeout'          => $container['configuration.merged']['unoconv.timeout'],
                    'unoconv.port'     => $container['configuration.merged']['unoconv.port'],
                )), $container['logger']);
            } catch (ExecutableNotFoundException $e) {
                throw new RuntimeException('Unable to create Unoconv driver', $e->getCode(), $e);
            }
        };

        $this['exiftool.exiftool'] = function (Container $container) {
            try {
                return new Exiftool($container['logger'], $container['configuration.merged']['exiftool.binary']);
            } catch (PHPExiftoolException $e) {
                throw new RuntimeException('Unable to create Exiftool driver', $e->getCode(), $e);
            }
        };

        $this['exiftool.classes-directory'] = function (Container $container) {
            $directory = $container['configuration.merged']['exiftool.classes-directory'];

            if (null === $directory) {
                $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'media-alchemyst-phpexiftool';
            }

            if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Unable to create PHPExiftool classes directory %s', $directory));
            }

            $phpExiftool = new PHPExiftool($directory, $container['logger']);

            if (!$phpExiftool->isClassesGenerated()) {
                $phpExiftool->generateClasses(array(InformationDumper::LISTOPTION_MWG), array('en'));
            }

            return $phpExiftool->getClassesRootDirectory();
        };

        $this['exiftool.rdf-parser'] = function (Container $container) {
            return new RDFParser($container['exiftool.classes-directory'], $container['logger']);
        };

        $this['exiftool.reader'] = function (Container $container) {
            return Reader::create(
                $container['exiftool.exiftool'],
                $container['exiftool.rdf-parser']
            );
        };

        $this['exiftool.writer'] = function (Container $container) {
            return Writer::create($container['exiftool.exiftool']);
        };

        $this['exiftool.preview-extractor'] = function (Container $container) {
            return new PreviewExtractor($container['exiftool.exiftool']);
        };

        $this['ghostscript.transcoder'] = function (Container $container) {
            try {
                return Transcoder::create(array_filter(array(
                    'gs.binaries' => $container['configuration.merged']['gs.binaries'],
                    'timeout'     => $container['configuration.merged']['gs.timeout'],
                )), $container['logger']);
            } catch (ExecutableNotFoundException $e) {
                throw new RuntimeException('Unable to create Ghostscript driver', $e->getCode(), $e);
            }
        };

        $this['mp4box'] = function (Container $container) {
            try {
                return MP4Box::create(array_filter(array(
                    'mp4box.binaries' => $container['configuration.merged']['mp4box.binaries'],
                    'timeout'         => $container['configuration.merged']['mp4box.timeout'],
                )), $container['logger']);
            } catch (ExecutableNotFoundException $e) {
                throw new RuntimeException('Unable to create MP4Box driver', $e->getCode(), $e);
            }
        };

        $this['mediavorus'] = function (Container $container) {
            $ffprobe = null;
            try {
                $ffprobe = $container['ffmpeg.ffprobe'];
            } catch (RuntimeException $e) {

            }

            return new MediaVorus(
                $container['exiftool.reader'],
                $container['exiftool.writer'],
                $ffprobe
            );
        };

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    public static function create()
    {
        return new static();
    }
}
