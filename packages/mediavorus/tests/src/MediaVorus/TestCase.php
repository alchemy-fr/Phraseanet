<?php

namespace MediaVorus;

use FFMpeg\Exception\ExecutableNotFoundException;
use FFMpeg\FFProbe;
use MediaVorus\Utils\AudioMimeTypeGuesser;
use MediaVorus\Utils\PostScriptMimeTypeGuesser;
use MediaVorus\Utils\RawImageMimeTypeGuesser;
use MediaVorus\Utils\VideoMimeTypeGuesser;
use PHPExiftool\InformationDumper;
use PHPExiftool\PHPExiftool;
use PHPExiftool\Reader;
use PHPExiftool\Writer;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Process\ExecutableFinder;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    private static $registered = false;
    private static $phpExiftool;

    protected function setUp(): void
    {
        if (!self::$registered) {
            $guesser = MimeTypes::getDefault();

            $guesser->registerGuesser(new RawImageMimeTypeGuesser());
            $guesser->registerGuesser(new PostScriptMimeTypeGuesser());
            $guesser->registerGuesser(new AudioMimeTypeGuesser());
            $guesser->registerGuesser(new VideoMimeTypeGuesser());

            self::$registered = true;
        }
    }

    /**
     * Directory where PHPExiftool tag classes are generated, shared with MediaVorus::create()
     */
    protected static function getExiftoolClassesDirectory()
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mediavorus-phpexiftool';
    }

    protected static function getPHPExiftool()
    {
        if (null === self::$phpExiftool) {
            $directory = static::getExiftoolClassesDirectory();

            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            $phpExiftool = new PHPExiftool($directory);

            if (!$phpExiftool->isClassesGenerated()) {
                $phpExiftool->generateClasses(array(InformationDumper::LISTOPTION_MWG), array('en'));
            }

            self::$phpExiftool = $phpExiftool;
        }

        return self::$phpExiftool;
    }

    protected function requireExiftool()
    {
        $finder = new ExecutableFinder();

        if (null === $finder->find('perl')) {
            $this->markTestSkipped('Perl is required to run exiftool');
        }
    }

    /**
     * @return Reader
     */
    protected function createReader()
    {
        $this->requireExiftool();

        return static::getPHPExiftool()->getFactory()->createReader();
    }

    /**
     * @return Writer
     */
    protected function createWriter()
    {
        $this->requireExiftool();

        return static::getPHPExiftool()->getFactory()->createWriter();
    }

    /**
     * @return FFProbe
     */
    protected function createFFProbe()
    {
        try {
            return FFProbe::create();
        } catch (ExecutableNotFoundException $e) {
            $this->markTestSkipped('Unable to find FFProbe, required for this test');
        }
    }

    /**
     * @return MediaVorus
     */
    protected function createMediaVorus()
    {
        return new MediaVorus($this->createReader(), $this->createWriter(), $this->createFFProbe());
    }
}
