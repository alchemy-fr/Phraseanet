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
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreationEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationFailedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
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
    /**
     * @var LoggerInterface
     */
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

    private function dispatch($eventName, RecordEvent $event)
    {
        $this->app['dispatcher']->dispatch($eventName, $event);
    }

    public function generateSubdefs(\record_adapter $record, array $wanted_subdefs = null)
    {
        if (null === $subdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType())) {
            $this->logger->info(sprintf('Nothing to do for %s', $record->getType()));

            return $this;
        }

        $this->dispatch(
            RecordEvents::SUB_DEFINITIONS_CREATION,
            new SubDefinitionsCreationEvent(
                $record,
                $wanted_subdefs
            )
        );

        $mediaCreated = [];
        foreach ($subdefs as $subdef) {
            $subdefname = $subdef->get_name();

            if ($wanted_subdefs && !in_array($subdefname, $wanted_subdefs)) {
                continue;
            }

            $pathdest = null;

            if ($record->has_subdef($subdefname) && $record->get_subdef($subdefname)->is_physically_present()) {
                $pathdest = $record->get_subdef($subdefname)->getRealPath();
                $record->get_subdef($subdefname)->remove_file();
                $this->logger->info(sprintf('Removed old file for %s', $subdefname));
                $record->clearSubdefCache($subdefname);
            }

            $pathdest = $this->generateSubdefPathname($record, $subdef, $pathdest);

            $this->dispatch(
                RecordEvents::SUB_DEFINITION_CREATION,
                new SubDefinitionCreationEvent(
                    $record,
                    $subdefname
                )
            );

            $this->logger->addInfo(sprintf('Generating subdef %s to %s', $subdefname, $pathdest));
            $this->generateSubdef($record, $subdef, $pathdest);

            if ($this->filesystem->exists($pathdest)) {
                $media = $this->mediavorus->guess($pathdest);

                \media_subdef::create($this->app, $record, $subdef->get_name(), $media);

                $this->dispatch(
                    RecordEvents::SUB_DEFINITION_CREATED,
                    new SubDefinitionCreatedEvent(
                        $record,
                        $subdefname,
                        $mediaCreated[$subdefname] = $media
                    )
                );
            }
            else {
                $this->dispatch(
                    RecordEvents::SUB_DEFINITION_CREATION_FAILED,
                    new SubDefinitionCreationFailedEvent(
                        $record,
                        $subdefname
                    )
                );
            }

            $record->clearSubdefCache($subdefname);
        }

        $this->dispatch(
            RecordEvents::SUB_DEFINITIONS_CREATED,
            new SubDefinitionsCreatedEvent(
                $record,
                $mediaCreated
            )
        );

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
            $this->logger->error(sprintf('Subdef generation failed for record %d with message %s', $record->getRecordId(), $e->getMessage()));
        }
    }

    private function generateSubdefPathname(\record_adapter $record, \databox_subdef $subdef, $oldVersion = null)
    {
        if ($oldVersion) {
            $pathdest = \p4string::addEndSlash(pathinfo($oldVersion, PATHINFO_DIRNAME));
        } else {
            $pathdest = \databox::dispatch($this->filesystem, $subdef->get_path());
        }

        return $pathdest . $record->getRecordId() . '_' . $subdef->get_name() . '.' . $this->getExtensionFromSpec($subdef->getSpecs());
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
        switch ($spec->getType()) {
            case SpecificationInterface::TYPE_IMAGE:
                return 'jpg';
            case SpecificationInterface::TYPE_ANIMATION:
                return 'gif';
            case SpecificationInterface::TYPE_AUDIO:
                return $this->getExtensionFromAudioCodec($spec->getAudioCodec());
            case SpecificationInterface::TYPE_VIDEO:
                return $this->getExtensionFromVideoCodec($spec->getVideoCodec());
            case SpecificationInterface::TYPE_SWF:
                return 'swf';
        }

        return null;
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
        switch ($audioCodec) {
            case 'flac':
                return 'flac';
            case 'libvorbis':
                return 'ogg';
            case 'libmp3lame':
                return 'mp3';
        }

        return null;
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
        switch ($videoCodec) {
            case 'libtheora':
                return 'ogv';
            case 'libvpx':
                return 'webm';
            case 'libx264':
                return 'mp4';
        }

        return null;
    }
}
