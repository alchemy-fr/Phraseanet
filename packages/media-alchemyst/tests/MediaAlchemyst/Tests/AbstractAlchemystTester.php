<?php

namespace MediaAlchemyst\Tests;

use PHPUnit\Framework\TestCase;
use MediaAlchemyst\DriversContainer;
use MediaVorus\MediaVorus;
use MediaVorus\Utils\AudioMimeTypeGuesser;
use MediaVorus\Utils\PostScriptMimeTypeGuesser;
use MediaVorus\Utils\RawImageMimeTypeGuesser;
use MediaVorus\Utils\VideoMimeTypeGuesser;
use Neutron\TemporaryFilesystem\Manager;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Process\ExecutableFinder;

abstract class AbstractAlchemystTester extends TestCase
{
    private static $guessersRegistered = false;

    /**
     * @return MediaVorus
     */
    public function getMediaVorus()
    {
        $this->requireExiftool();

        if (!self::$guessersRegistered) {
            $guesser = MimeTypes::getDefault();
            $guesser->registerGuesser(new AudioMimeTypeGuesser());
            $guesser->registerGuesser(new PostScriptMimeTypeGuesser());
            $guesser->registerGuesser(new RawImageMimeTypeGuesser());
            $guesser->registerGuesser(new VideoMimeTypeGuesser());
            self::$guessersRegistered = true;
        }

        $drivers = new DriversContainer();

        return $drivers['mediavorus'];
    }

    public function requireExiftool()
    {
        $finder = new ExecutableFinder();

        if (null === $finder->find('perl')) {
            $this->markTestSkipped('Perl is required to run exiftool');
        }
    }

    public function requireBinary($name)
    {
        $finder = new ExecutableFinder();

        if (null === $finder->find($name)) {
            $this->markTestSkipped(sprintf('%s is required for this test', $name));
        }
    }

    public function getFsManager()
    {
        $fs = new Filesystem();

        return new Manager(new TemporaryFilesystem($fs), $fs);
    }
}
