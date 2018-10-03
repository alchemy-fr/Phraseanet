<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Media;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Record\MediaSubstitutedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaVorus\Media\MediaInterface;
use MediaVorus\MediaVorus;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SubdefSubstituer
{
    private $alchemyst;
    private $fs;
    private $mediavorus;

    public function __construct(Application $app, FilesystemService $fs, Alchemyst $alchemyst, MediaVorus $mediavorus, EventDispatcherInterface $dispatcher)
    {
        $this->alchemyst = $alchemyst;
        $this->app = $app;
        $this->fs = $fs;
        $this->mediavorus = $mediavorus;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param \record_adapter $record
     * @param string $name
     * @param MediaInterface $media
     * @param bool $adapt
     *
     * @deprecated use {@link self::substituteDocument} or {@link self::substituteSubdef} instead
     */
    public function substitute(\record_adapter $record, $name, MediaInterface $media, $adapt = true)
    {
        if ($name == 'document') {
            $this->substituteDocument($record, $media, $adapt);

            return;
        }

        $this->substituteSubdef($record, $name, $media, $adapt);
    }

    /**
     * @param \record_adapter $record
     * @param MediaInterface $media
     * @param bool $shouldSubdefsBeRebuilt
     */
    public function substituteDocument(\record_adapter $record, MediaInterface $media, $shouldSubdefsBeRebuilt = true)
    {
        /** @var \SplFileInfo $file */
        $file = $media->getFile();

        $source = $file->getRealPath();
        $target = $this->fs->generateDocumentFilename($record, $file);

        $target  = $this->fs->writeMediaSourceFile($record->getDatabox(), $source, $target);

        $media = $this->mediavorus->guess($target);

        $this->createMediaSubdef($record, 'document', $media);

        $record->write_metas();

        if ($shouldSubdefsBeRebuilt) {
            $record->rebuild_subdefs();
        }

        $this->dispatcher->dispatch(RecordEvents::MEDIA_SUBSTITUTED, new MediaSubstitutedEvent($record));
    }

    /**
     * @param \record_adapter $record
     * @param string $name
     * @param MediaInterface $media
     * @param bool $adapt
     */
    public function substituteSubdef(\record_adapter $record, $name, MediaInterface $media, $adapt = true)
    {
        if ($name == 'document') {
            throw new \RuntimeException('Cannot substitute documents, only subdefs allowed');
        }

        $type = $record->isStory() ? 'image' : $record->getType();
        $databox_subdef = $record->getDatabox()->get_subdef_structure()->get_subdef($type, $name);

        if ($this->isOldSubdefPresent($record, $name)) {
            $path_file_dest = $record->get_subdef($name)->getRealPath();
            $record->get_subdef($name)->remove_file();
            $record->clearSubdefCache($name);
        } else {
            $path_file_dest = $this->fs->generateSubdefSubstitutionPathname($record, $databox_subdef);
        }

        if($adapt) {
            try {
                $this->alchemyst->turnInto(
                    $media->getFile()->getRealPath(),
                    $path_file_dest,
                    $databox_subdef->getSpecs()
                );
            } catch (MediaAlchemystException $e) {
                return;
            }
        } else {
            $this->fs->copy($media->getFile()->getRealPath(), $path_file_dest);
        }

        $this->fs->chmod($path_file_dest, 0760);
        $media = $this->mediavorus->guess($path_file_dest);

        $this->createMediaSubdef($record, $name, $media);

        if ($databox_subdef->isMetadataUpdateRequired()) {
            $record->write_metas();
        }

        $this->dispatcher->dispatch(RecordEvents::MEDIA_SUBSTITUTED, new MediaSubstitutedEvent($record));
    }

    /**
     * @param \record_adapter $record
     * @param string $name
     * @return bool
     */
    private function isOldSubdefPresent(\record_adapter $record, $name)
    {
        return $record->has_subdef($name) && $record->get_subdef($name)->is_physically_present();
    }

    /**
     * @param \record_adapter $record
     * @param string $name
     * @param MediaInterface $media
     */
    private function createMediaSubdef(\record_adapter $record, $name, MediaInterface $media)
    {
        $subdef = \media_subdef::create($this->app, $record, $name, $media);
        $subdef->set_substituted(true);

        $record->delete_data_from_cache(\record_adapter::CACHE_SUBDEFS);
    }
}
