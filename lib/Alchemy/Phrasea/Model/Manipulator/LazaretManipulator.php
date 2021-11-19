<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Border\Attribute\MetaField;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPExiftool\Driver\Metadata\Metadata;
use Symfony\Component\Filesystem\Exception\IOException;

// use Symfony\Component\Filesystem\Filesystem;


class LazaretManipulator
{
    /** @var Application */
    private $app;
    /** @var EntityRepository */
    private $repository;
    /**
     * @var Filesystem
     */
    private $fileSystem;
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(Application $app, EntityRepository $repository, Filesystem $fileSystem, EntityManager $entityManager)
    {
        $this->app = $app;
        $this->repository = $repository;
        $this->fileSystem = $fileSystem;
        $this->entityManager = $entityManager;
    }

    public function deny($lazaret_id)
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        /** @var LazaretFile $lazaretFile */
        $lazaretFile = $this->repository->find($lazaret_id);
        if (null === $lazaretFile) {
            $ret['message'] = $this->app->trans('File is not present in quarantine anymore, please refresh');

            return $ret;
        }

        try {
            $this->denyLazaretFile($lazaretFile);
            $ret['success'] = true;
        } catch (\Exception $e) {
            // No-op
        }

        return $ret;
    }

    /**
     * Empty lazaret
     *
     * @param int     $maxTodo
     *
     * @return Array
     */
    public function clear($maxTodo = -1)
    {
        $ret = array(
            'success' => false,
            'message' => '',
            'result'  => array(
                'tobedone'  => 0,
                'done'      => 0,
                'todo'      => 0,
                'max'       => '',
            )
        );

        if( $maxTodo <= 0) {
            $maxTodo = -1;      // all
        }
        $ret['result']['max'] = $maxTodo;

        $ret['result']['tobedone'] = (int) $this->repository->createQueryBuilder('id')
            ->select('COUNT(id)')
            ->getQuery()
            ->getSingleScalarResult();

        if($maxTodo == -1) {
            // all
            $lazaretFiles = $this->repository->findAll();
        } else {
            // limit maxTodo
            $lazaretFiles = $this->repository->findBy(array(), null, $maxTodo);
        }

        $this->entityManager->beginTransaction();

        try {
            foreach ($lazaretFiles as $lazaretFile) {
                $this->denyLazaretFile($lazaretFile);
                $ret['result']['done']++;
            }
            $this->entityManager->commit();
            $ret['success'] = true;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $ret['message'] = $this->app->trans('An error occured');
        }
        $ret['result']['todo'] = $ret['result']['tobedone'] - $ret['result']['done'];

        return $ret;
    }


    public function add($file_id, $keepAttributes=true, Array $attributesToKeep=[])
    {
        $ret = ['success' => false, 'message' => '', 'result'  => []];

        /* @var LazaretFile $lazaretFile */
        $lazaretFile = $this->repository->find($file_id);

        if (null === $lazaretFile) {
            $ret['message'] = $this->app->trans('File is not present in quarantine anymore, please refresh');

            return $ret;
        }

        $path = $this->app['tmp.lazaret.path'];
        $lazaretFileName = $path .'/'.$lazaretFile->getFilename();
        $lazaretThumbFileName = $path .'/'.$lazaretFile->getThumbFilename();

        try {
            $borderFile = Border\File::buildFromPathfile(
                $lazaretFileName,
                $lazaretFile->getCollection($this->app),
                $this->app,
                $lazaretFile->getOriginalName()
            );
        }
        catch(\Exception $e) {
            // the file is not in tmp anymore ?
            // delete the quarantine item
            $this->denyLazaretFile($lazaretFile);
            $ret['message'] = $this->app->trans('File is not present in quarantine anymore, please refresh');

            return $ret;
        }

        try {
            //Post record creation

            /** @var \record_adapter $record */
            $record = null;
            $callBack = function ($element) use (&$record) {
                $record = $element;
            };

            //Force creation record
            $this->getBorderManager()->process(
                $lazaretFile->getSession(),
                $borderFile,
                $callBack,
                Border\Manager::FORCE_RECORD
            );

            if ($keepAttributes) {
                //add attribute

                $metaFields = new Border\MetaFieldsBag();
                $metadataBag = new Border\MetadataBag();

                foreach ($lazaretFile->getAttributes() as $attr) {
                    //Check which ones to keep
                    if (!!count($attributesToKeep)) {
                        if (!in_array($attr->getId(), $attributesToKeep)) {
                            continue;
                        }
                    }

                    try {
                        $attribute = Border\Attribute\Factory::getFileAttribute($this->app, $attr->getName(), $attr->getValue());
                    } catch (\InvalidArgumentException $e) {
                        continue;
                    }

                    switch ($attribute->getName()) {
                        case AttributeInterface::NAME_METADATA:
                            /** @var Metadata $value */
                            $value = $attribute->getValue();
                            // $metadataBag->set($value->getTag()->getTagname(), new Metadata($value->getTag(), $value->getValue()));
                            $metadataBag->set($value->getTag()->getTagname(), $value);
                            break;
                        case AttributeInterface::NAME_STORY:
                            /** @var \record_adapter $value */
                            $value = $attribute->getValue();
                            $value->appendChild($record);
                            break;
                        case AttributeInterface::NAME_STATUS:
                            $record->setStatus($attribute->getValue());
                            break;
                        case AttributeInterface::NAME_METAFIELD:
                            /** @var MetaField $attribute */
                            $metaFields->set($attribute->getField()->get_name(), $attribute);
                            break;
                    }
                }

                /* todo: better to to do only one set_metadatas ? */
                $data = $metadataBag->toMetadataArray($record->getDatabox()->get_meta_structure());

                $record->set_metadatas($data);

                $fields = $metaFields->toMetadataArray($record->getDatabox()->get_meta_structure());

                $record->set_metadatas($fields);


                // order to write meta in file

                $this->app['dispatcher']->dispatch(WorkerEvents::RECORDS_WRITE_META,
                    new RecordsWriteMetaEvent([$record->getRecordId()], $record->getDataboxId()));
            }

            //Delete lazaret file
            $this->entityManager->remove($lazaretFile);
            $this->entityManager->flush();

            $ret['result']['record_id'] = $record->getRecordId();

            $ret['success'] = true;
        } catch (\Exception $e) {
            $ret['message'] = $this->app->trans('An error occured');
        }

        try {
            $this->fileSystem->remove([$lazaretFileName, $lazaretThumbFileName]);
        } catch (IOException $e) {
            // no-op
        }

        return $ret;
    }

    /**
     * @return Border\Manager
     */
    private function getBorderManager()
    {
        return $this->app['border-manager'];
    }

    protected function denyLazaretFile(LazaretFile $lazaretFile)
    {
        $path = $this->app['tmp.lazaret.path'];
        $lazaretFileName = $path .'/'.$lazaretFile->getFilename();
        $lazaretThumbFileName = $path .'/'.$lazaretFile->getThumbFilename();

        $this->entityManager->remove($lazaretFile);
        $this->entityManager->flush();

        try {
            $this->fileSystem->remove([$lazaretFileName, $lazaretThumbFileName]);
        } catch (IOException $e) {
            // no-op
        }

        return $this;
    }


}
