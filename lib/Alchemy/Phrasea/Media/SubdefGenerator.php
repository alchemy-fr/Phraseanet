<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

use Alchemy\Phrasea\Application;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\SpecificationInterface;
use MediaVorus\MediaVorus;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class SubdefGenerator
{
    private $app;
    private $alchemyst;
    private $filesystem;
    private $logger;
    private $mediavorus;

    public function __construct(Application $app, Alchemyst $alchemyst, Filesystem $filesystem, MediaVorus $mediavorus, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->alchemyst = $alchemyst;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->mediavorus = $mediavorus;
    }

    public function generateSubdefs(\record_adapter $record, array $wanted_subdefs = null)
    {
        if (null === $subdefs = $record->get_databox()->get_subdef_structure()->getSubdefGroup($record->get_type())) {
            $this->logger->info(sprintf('Nothing to do for %s', $record->get_type()));

            return;
        }

        foreach ($subdefs as $subdef) {
            $subdefname = $subdef->get_name();

            if ($wanted_subdefs && !in_array($subdefname, $wanted_subdefs)) {
                continue;
            }

            $pathdest = null;

            if ($record->has_subdef($subdefname) && $record->get_subdef($subdefname)->is_physically_present()) {
                $pathdest = $record->get_subdef($subdefname)->get_pathfile();
                $record->get_subdef($subdefname)->remove_file();
                $this->logger->info(sprintf('Removed old file for %s', $subdefname));
                $record->clearSubdefCache($subdefname);
            }

            $pathdest = $this->generateSubdefPathname($record, $subdef, $pathdest);

            $this->logger->addInfo(sprintf('Generating subdef %s to %s', $subdefname, $pathdest));
            $this->generateSubdef($record, $subdef, $pathdest);

            if ($this->filesystem->exists($pathdest)) {
                $media = $this->mediavorus->guess($pathdest);

                \media_subdef::create($this->app, $record, $subdef->get_name(), $media);
            }

            $record->clearSubdefCache($subdefname);

            if (in_array($subdefname, ['thumbnail', 'thumbnailgif', 'preview'])) {
                $this->app['elasticsearch.engine']->updateRecord($record);
            }
        }

        return $this;
    }

    private function generateSubdef(\record_adapter $record, \databox_subdef $subdef_class, $pathdest)
    {
        try {
            if (null === $record->get_hd_file()) {
                $this->logger->addInfo('No HD file found, aborting');

                return;
            }

            $this->alchemyst->turnInto($record->get_hd_file()->getPathname(), $pathdest, $subdef_class->getSpecs());
        } catch (MediaAlchemystException $e) {
            $this->logger->error(sprintf('Subdef generation failed for record %d with message %s', $record->get_record_id(), $e->getMessage()));
        }
    }

    private function generateSubdefPathname(\record_adapter $record, \databox_subdef $subdef, $oldVersion = null)
    {
        if ($oldVersion) {
            $pathdest = \p4string::addEndSlash(pathinfo($oldVersion, PATHINFO_DIRNAME));
        } else {
            $pathdest = \databox::dispatch($this->filesystem, $subdef->get_path());
        }

        return $pathdest . $record->get_record_id() . '_' . $subdef->get_name() . '.' . $this->getExtensionFromSpec($subdef->getSpecs());
    }

    /**
     * Get the extension from MediaAlchemyst specs
     *
     * @param SpecificationInterface $spec
     *
     * @return string
     */
    private function getExtensionFromSpec(SpecificationInterface $spec)
    {
        $extension = null;

        switch (true) {
            case $spec->getType() === SpecificationInterface::TYPE_IMAGE:
                $extension = 'jpg';
                break;
            case $spec->getType() === SpecificationInterface::TYPE_ANIMATION:
                $extension = 'gif';
                break;
            case $spec->getType() === SpecificationInterface::TYPE_AUDIO:
                $extension = $this->getExtensionFromAudioCodec($spec->getAudioCodec());
                break;
            case $spec->getType() === SpecificationInterface::TYPE_VIDEO:
                $extension = $this->getExtensionFromVideoCodec($spec->getVideoCodec());
                break;
            case $spec->getType() === SpecificationInterface::TYPE_SWF:
                $extension = 'swf';
                break;
        }

        return $extension;
    }

    /**
     * Get the extension from audiocodec
     *
     * @param string $audioCodec
     *
     * @return string
     */
    private function getExtensionFromAudioCodec($audioCodec)
    {
        $extension = null;

        switch ($audioCodec) {
            case 'flac':
                $extension = 'flac';
                break;
            case 'libvorbis':
                $extension = 'ogg';
                break;
            case 'libmp3lame':
                $extension = 'mp3';
                break;
        }

        return $extension;
    }

    /**
     * Get the extension from videocodec
     *
     * @param string $videoCodec
     *
     * @return string
     */
    private function getExtensionFromVideoCodec($videoCodec)
    {
        $extension = null;

        switch ($videoCodec) {
            case 'libtheora':
                $extension = 'ogv';
                break;
            case 'libvpx':
                $extension = 'webm';
                break;
            case 'libx264':
                $extension = 'mp4';
                break;
        }

        return $extension;
    }
}
