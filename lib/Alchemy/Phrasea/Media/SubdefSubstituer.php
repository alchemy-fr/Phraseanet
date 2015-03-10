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
use Alchemy\Phrasea\Core\Event\Record\RecordMediaSubstitutedEvent;
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

    public function substitute(\record_adapter $record, $name, MediaInterface $media)
    {
        $newfilename = $record->get_record_id() . '_0_' . $name . '.' . $media->getFile()->getExtension();

        $subdef_def = false;

        if ($name == 'document') {
            $baseprefs = $record->get_databox()->get_sxml_structure();
            $pathhd = \p4string::addEndSlash((string) ($baseprefs->path));

            $filehd = $record->get_record_id() . "_document." . strtolower($media->getFile()->getExtension());
            $pathhd = \databox::dispatch($this->fs, $pathhd);

            $this->fs->copy($media->getFile()->getRealPath(), $pathhd . $filehd, true);

            $subdefFile = $pathhd . $filehd;
            $meta_writable = true;
        } else {
            $type = $record->is_grouping() ? 'image' : $record->get_type();
            $subdef_def = $record->get_databox()->get_subdef_structure()->get_subdef($type, $name);

            if ($record->has_subdef($name) && $record->get_subdef($name)->is_physically_present()) {
                $path_file_dest = $record->get_subdef($name)->get_pathfile();
                $record->get_subdef($name)->remove_file();
                $record->clearSubdefCache($name);
            } else {
                $path = \databox::dispatch($this->fs, $subdef_def->get_path());
                $this->fs->mkdir($path, 0750);
                $path_file_dest = $path . $newfilename;
            }

            try {
                $this->alchemyst->turnInto(
                    $media->getFile()->getRealPath(),
                    $path_file_dest,
                    $subdef_def->getSpecs()
                );
            } catch (MediaAlchemystException $e) {
                return;
            }

            $subdefFile = $path_file_dest;

            $meta_writable = $subdef_def->meta_writeable();
        }

        $this->fs->chmod($subdefFile, 0760);
        $media = $this->mediavorus->guess($subdefFile);

        \media_subdef::create($this->app, $record, $name, $media);

        $record->delete_data_from_cache(\record_adapter::CACHE_SUBDEFS);

        if ($meta_writable) {
            $record->write_metas();
        }

        if ($name == 'document') {
            $record->rebuild_subdefs();
        }

        $this->dispatcher->dispatch(RecordEvents::MEDIA_SUBSTITUTED, new RecordMediaSubstitutedEvent($record));
    }
}
