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
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaVorus\Media\MediaInterface;
use MediaVorus\MediaVorus;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

class SubdefSubstituer
{
    private $alchemyst;
    private $fs;
    private $mediavorus;

    public function __construct(Application $app, Filesystem $fs, Alchemyst $alchemyst, MediaVorus $mediavorus, EventDispatcherInterface $dispatcher)
    {
        $this->alchemyst = $alchemyst;
        $this->app = $app;
        $this->fs = $fs;
        $this->mediavorus = $mediavorus;
        $this->dispatcher = $dispatcher;
    }

    public function substitute(\record_adapter $record, $name, MediaInterface $media, $adapt = true)
    {
        if ($name == 'document') {
            $baseprefs = $record->getDatabox()->get_sxml_structure();
            $pathhd = \p4string::addEndSlash((string) ($baseprefs->path));

            $filehd = $record->getRecordId() . "_document." . strtolower($media->getFile()->getExtension());
            $pathhd = \databox::dispatch($this->fs, $pathhd);

            $this->fs->copy($media->getFile()->getRealPath(), $pathhd . $filehd, true);

            $subdefFile = $pathhd . $filehd;
            $meta_writable = true;
        } else {
            $type = $record->isStory() ? 'image' : $record->get_type();
            $subdef_def = $record->getDatabox()->get_subdef_structure()->get_subdef($type, $name);

            if ($record->has_subdef($name) && $record->get_subdef($name)->is_physically_present()) {
                $path_file_dest = $record->get_subdef($name)->getRealPath();
                $record->get_subdef($name)->remove_file();
                $record->clearSubdefCache($name);
            } else {
                $path = \databox::dispatch($this->fs, $subdef_def->get_path());
                $this->fs->mkdir($path, 0750);
                $path_file_dest = $path . $record->getRecordId() . '_0_' . $name . '.' . $media->getFile()->getExtension();
            }

            if($adapt) {
                try {
                    $this->alchemyst->turnInto(
                        $media->getFile()->getRealPath(),
                        $path_file_dest,
                        $subdef_def->getSpecs()
                    );
                } catch (MediaAlchemystException $e) {
                    return;
                }
            } else {
                $this->fs->copy($media->getFile()->getRealPath(), $path_file_dest);
            }

            $subdefFile = $path_file_dest;

            $meta_writable = $subdef_def->isMetadataUpdateRequired();
        }

        $this->fs->chmod($subdefFile, 0760);
        $media = $this->mediavorus->guess($subdefFile);

        $subdef = \media_subdef::create($this->app, $record, $name, $media);
        $subdef->set_substituted(true);

        $record->delete_data_from_cache(\record_adapter::CACHE_SUBDEFS);

        if ($meta_writable) {
            $record->write_metas();
        }

        if ($name == 'document') {
            $record->rebuild_subdefs();
        }

        $this->dispatcher->dispatch(RecordEvents::MEDIA_SUBSTITUTED, new MediaSubstitutedEvent($record));
    }
}
