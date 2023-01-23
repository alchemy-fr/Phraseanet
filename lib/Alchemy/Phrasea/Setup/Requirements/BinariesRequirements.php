<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\RequirementCollection;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\PhpExecutableFinder;

class BinariesRequirements extends RequirementCollection implements RequirementInterface
{
    const FILE_VERSION = '5.04';
    const IMAGICK_VERSION = '6.2.9';
    const SWFTOOLS_VERSION = '0.9.0';
    const UNOCONV_VERSION = '0.5';
    const MP4BOX_VERSION = '0.4.0';
    const EXIFTOOL_VERSION = '9.15';

    public function __construct($binaries = [])
    {
        $this->setName('Binaries');

        $phpFinder = new PhpExecutableFinder();
        $finder = new ExecutableFinder();

        $phpCLI = isset($binaries['php_binary']) ? $binaries['php_binary'] : $phpFinder->find();

        $this->addRequirement(
            null !== $phpCLI && is_executable($phpCLI),
            'PHP CLI is required to run Phraseanet task manager',
            'Please reinstall PHP with CLI support'
        );

        $fileCommand = $finder->find('file');

        if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRequirement(
                is_executable($fileCommand),
                'Phraseanet requires the Unix `file` command',
                'Please install latest file command'
            );

            $output = null;
            exec($fileCommand . ' --version 2>&1', $output);
            $data = sscanf($output[0], 'file-%s');
            $version = $data[0];

            $this->addRecommendation(
                version_compare($version, static::FILE_VERSION, '>='),
                sprintf('Phraseanet recommends `file` version %s or higher', static::FILE_VERSION),
                'Please install latest file command'
            );
        }

        $exiftool = __DIR__ . '/../../../../../vendor/exiftool/exiftool/exiftool' . (defined('PHP_WINDOWS_VERSION_BUILD') ? '.exe' : '');

        $this->addRequirement(
            is_file($exiftool) && is_executable($exiftool),
            'Exiftool is required for reading, writing and editing meta information',
            'Please install Exiftool'
        );

        if (is_file($exiftool) && is_executable($exiftool)) {
            $output = null;
            exec($exiftool . ' -ver', $output);
            $version = $output[0];
            $this->addRequirement(
                version_compare(static::EXIFTOOL_VERSION, $version, '<='),
                sprintf('Exiftool version %s or higher is required (%s provided)', static::EXIFTOOL_VERSION, $version),
                'Please update to a more recent version.'
            );
        }

        $pdf2swf = isset($binaries['pdf2swf_binary']) ? $binaries['pdf2swf_binary'] : $finder->find('pdf2swf');

        if (null !== $pdf2swf) {
            $output = null;
            exec($pdf2swf . ' --version', $output);
            $data = sscanf($output[0], 'pdf2swf - part of swftools %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRecommendation(
                version_compare(static::SWFTOOLS_VERSION, $version, '<='),
                sprintf('SWFTools (pdf2swf) version %s or higher is required (%s provided)', static::SWFTOOLS_VERSION, $version),
                'Please update to a more recent version.'
            );
        }

        $unoconv = isset($binaries['unoconv_binary']) ? $binaries['unoconv_binary'] : $finder->find('unoconv');

        $this->addRecommendation(
            null !== $unoconv && is_executable($unoconv),
            'Unoconv is required for documents (Word, Excel, etc...) support',
            'Please install Unoconv'
        );

        if (null !== $unoconv) {
            $output = null;
            exec($unoconv . ' --version 2>&1', $output);
            $data = sscanf($output[0], 'unoconv %d.%d');
            $version = sprintf('%d.%d', $data[0], $data[1]);

            $this->addRecommendation(
                version_compare(static::UNOCONV_VERSION, $version, '<='),
                sprintf('Unoconv version %s or higher is required (%s provided)', static::UNOCONV_VERSION, $version),
                'Please update to a more recent version.'
            );
        }

        $swfextract = isset($binaries['swf_extract_binary']) ? $binaries['swf_extract_binary'] : $finder->find('swfextract');

        $this->addRecommendation(
            null !== $swfextract && is_executable($swfextract),
            'SWFTools (swfextract) are required for flash files support',
            'Please install SWFTools (http://www.swftools.org/)'
        );

        if (null !== $swfextract) {
            $output = null;
            exec($swfextract . ' --version', $output);
            $data = sscanf($output[0], 'swfextract - part of swftools %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRecommendation(
                version_compare(static::SWFTOOLS_VERSION, $version, '<='),
                sprintf('SWFTools (swfextract) version %s or higher is required (%s provided)', static::SWFTOOLS_VERSION, $version),
                'Please update to a more recent version.'
            );
        }

        $swfrender = isset($binaries['swf_render_binary']) ? $binaries['swf_render_binary'] : $finder->find('swfrender');

        $this->addRecommendation(
            null !== $swfrender && is_executable($swfrender),
            'SWFTools (swfrender) are required for flash files support',
            'Please install SWFTools (http://www.swftools.org/)'
        );

        if (null !== $swfrender) {
            $output = null;
            exec($swfrender . ' --version', $output);
            $data = sscanf($output[0], 'swfrender - part of swftools %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRecommendation(
                version_compare(static::SWFTOOLS_VERSION, $version, '<='),
                sprintf('SWFTools (swfrender) version %s or higher is required (%s provided)', static::SWFTOOLS_VERSION, $version),
                'Please update to a more recent version.'
            );
        }

        $mp4box = isset($binaries['mp4box_binary']) ? $binaries['mp4box_binary'] : $finder->find('MP4Box');

        $this->addRecommendation(
            null !== $mp4box && is_executable($mp4box),
            'MP4Box is required for video support',
            'Please install MP4Box'
        );

        if (null !== $mp4box) {
            $output = null;
            exec($mp4box . ' -version 2>&1', $output);
            $data = sscanf($output[0], 'MP4Box - GPAC version %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRecommendation(
                version_compare(static::MP4BOX_VERSION, $version, '<='),
                sprintf('MP4Box version %s or higher is required (%s provided)', static::MP4BOX_VERSION, $version),
                'Please update to a more recent version.'
            );
        }

        $pdftotext = isset($binaries['pdftotext_binary']) ? $binaries['pdftotext_binary'] : $finder->find('pdftotext');

        $this->addRecommendation(
            null !== $pdftotext && is_executable($pdftotext),
            'XPDF is required for PDF indexation',
            'Please install XPDF'
        );

        $ffmpeg = isset($binaries['ffmpeg_binary']) ? $binaries['ffmpeg_binary'] : $finder->find('ffmpeg');

        if (null === $ffmpeg) {
            $ffmpeg = $finder->find('avconv');
        }

        $this->addRecommendation(
            null !== $ffmpeg && is_executable($ffmpeg),
            'FFMpeg (or libav-tools) is required for Video processing',
            'Please install FFMpeg (or libav-tools)'
        );

        $ffprobe = isset($binaries['ffprobe_binary']) ? $binaries['ffprobe_binary'] : $finder->find('ffprobe');

        if (null === $ffprobe) {
            $ffprobe = $finder->find('avprobe');
        }

        $this->addRecommendation(
            null !== $ffprobe && is_executable($ffprobe),
            'FFProbe (or avprobe) is required for Video processing',
            'Please install FFProbe (or avprobe)'
        );
    }
}
