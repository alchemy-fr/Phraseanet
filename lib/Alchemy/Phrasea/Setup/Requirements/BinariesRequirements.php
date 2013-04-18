<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\System\RequirementCollection;
use Symfony\Component\Process\ExecutableFinder;

class BinariesRequirements extends RequirementCollection
{
    const REQUIRED_PHP_VERSION = '5.3.3';

    public function __construct($binaries = array())
    {
        $this->setName('Binaries');

        $finder = new ExecutableFinder();

        $phpCLI = isset($binaries['php_binary']) ? $binaries['php_binary'] : $finder->find('php');

        $this->addRequirement(
            null !== $phpCLI && is_executable($phpCLI),
            'PHP CLI is required to run Phraseanet task manager',
            'Please reinstall PHP with CLI support'
        );

        $indexer = $finder->find('phraseanet_indexer');

        $this->addRecommendation(
            null !== $indexer && is_executable($indexer),
            'Phraseanet Indexer is required to use Phrasea search-engine',
            'Please install latest Phraseanet Indexer (https://github.com/alchemy-fr/Phraseanet-Indexer)'
        );

        $convert = isset($binaries['convert_binary']) ? $binaries['convert_binary'] : $finder->find('convert');

        $this->addRequirement(
            null !== $convert && is_executable($convert),
            'ImageMagick Convert is required',
            'Please install ImageMagick'
        );

        if (null !== $convert) {
            $output = null;
            exec($convert . ' --version', $output);
            $data = sscanf($output[0], 'Version: ImageMagick %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRequirement(
                version_compare('6.2.9', $version, '<'),
                'Convert version 6.2.9 or higher is required ('.$version.' provided)',
                'Please update to a more recent version'
            );
        }

        $composite = isset($binaries['composite_binary']) ? $binaries['composite_binary'] : $finder->find('composite');

        $this->addRequirement(
            null !== $composite && is_executable($composite),
            'ImageMagick Composite is required',
            'Please install ImageMagick'
        );

        if (null !== $composite) {
            $output = null;
            exec($composite . ' --version', $output);
            $data = sscanf($output[0], 'Version: ImageMagick %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRequirement(
                version_compare('6.2.9', $version, '<'),
                'Composite version 6.2.9 or higher is required ('.$version.' provided)',
                'Please update to a more recent version.'
            );
        }

        $pdf2swf = isset($binaries['pdf2swf_binary']) ? $binaries['pdf2swf_binary'] : $finder->find('pdf2swf');

        $this->addRecommendation(
            null !== $pdf2swf && is_executable($pdf2swf),
            'SWFTools are required for documents (Word, Excel, PDF, etc...) support',
            'Please install SWFTools (http://www.swftools.org/)'
        );

        if (null !== $pdf2swf) {
            $output = null;
            exec($pdf2swf . ' --version', $output);
            $data = sscanf($output[0], 'pdf2swf - part of swftools %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRecommendation(
                version_compare('0.9.0', $version, '<='),
                'SWFTools (pdf2swf) version 0.9.0 or higher is required ('.$version.' provided)',
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
            exec($unoconv . ' --version', $output);
            $data = sscanf($output[0], 'unoconv %d.%d');
            $version = sprintf('%d.%d', $data[0], $data[1]);

            $this->addRecommendation(
                version_compare('0.5', $version, '<='),
                'Unoconv version 0.5 or higher is required ('.$version.' provided)',
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
                version_compare('0.9.0', $version, '<='),
                'SWFTools (swfextract) version 0.9.0 or higher is required ('.$version.' provided)',
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
                version_compare('0.9.0', $version, '<='),
                'SWFTools (swfrender) version 0.9.0 or higher is required ('.$version.' provided)',
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
            exec($mp4box . ' -version', $output);
            $data = sscanf($output[0], 'MP4Box - GPAC version %d.%d.%d');
            $version = sprintf('%d.%d.%d', $data[0], $data[1], $data[2]);

            $this->addRecommendation(
                version_compare('0.4.0', $version, '<='),
                'MP4Box version 0.4.0 or higher is required ('.$version.' provided)',
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
