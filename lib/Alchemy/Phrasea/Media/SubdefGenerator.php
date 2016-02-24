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
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreatedEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionsCreationEvent;
use Alchemy\Phrasea\Core\Event\Record\SubDefinitionCreationFailedEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Filesystem\FilesystemService;
use MediaAlchemyst\Alchemyst;
use MediaVorus\MediaVorus;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use Psr\Log\LoggerInterface;

class SubdefGenerator
{
    use DispatcherAware;

    private $app;
    private $alchemyst;
    private $filesystem;
    /**
     * @var LoggerInterface
     */
    private $logger;
    private $mediavorus;

    public function __construct(Application $app, Alchemyst $alchemyst, FilesystemService $filesystem, MediaVorus $mediavorus, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->alchemyst = $alchemyst;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->mediavorus = $mediavorus;
    }

    public function generateSubdefs(\record_adapter $record, array $wanted_subdefs = null)
    {
        if (null === $subdefs = $record->getDatabox()->get_subdef_structure()->getSubdefGroup($record->getType())) {
            $this->logger->info(sprintf('Nothing to do for %s', $record->getType()));
            $subdefs = [];
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

            $pathdest = $this->filesystem->generateSubdefPathname($record, $subdef, $pathdest);

            $this->dispatch(
                RecordEvents::SUB_DEFINITION_CREATION,
                new SubDefinitionCreationEvent(
                    $record,
                    $subdefname
                )
            );

            $this->logger->info(sprintf('Generating subdef %s to %s', $subdefname, $pathdest));
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
    }

    private function generateSubdef(\record_adapter $record, \databox_subdef $subdef_class, $pathdest)
    {
        try {
            if (null === $record->get_hd_file()) {
                $this->logger->info('No HD file found, aborting');

                return;
            }

            $this->alchemyst->turnInto($record->get_hd_file()->getPathname(), $pathdest, $subdef_class->getSpecs());
        } catch (MediaAlchemystException $e) {
            $this->logger->error(sprintf('Subdef generation failed for record %d with message %s', $record->getRecordId(), $e->getMessage()));
        }
    }
}
