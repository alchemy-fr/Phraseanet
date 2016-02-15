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
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Border;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Repositories\LazaretFileRepository;
use Doctrine\Common\Persistence\ObjectManager;
use PHPExiftool\Driver\Metadata\Metadata;
use Symfony\Component\Filesystem\Exception\IOException;


class LazaretManipulator
{
    use FilesystemAware;

    /** @var Application */
    private $app;
    /** @var LazaretFileRepository */
    private $repository;
    /** @var ObjectManager */
    private $manager;

    public function __construct(Application $app, LazaretFileRepository $repository, ObjectManager $manager)
    {
        $this->app = $app;
        $this->repository = $repository;
        $this->manager = $manager;
    }

    public function deny($lazaret_id)
    {
        $ret = ['success' => false, 'message' => ''];

        /** @var LazaretFile $lazaretFile */
        $lazaretFile = $this->getLazaretFileRepository()->find($lazaret_id);
        if (null === $lazaretFile) {
            $ret['message'] = $this->app->trans('File is not present in quarantine anymore, please refresh');

            return $ret;
        }

        try {
            $path = $this->app['tmp.lazaret.path'];
            $lazaretFileName = $path .'/'.$lazaretFile->getFilename();
            $lazaretThumbFileName = $path .'/'.$lazaretFile->getThumbFilename();

            $this->manager->remove($lazaretFile);
            $this->manager->flush();

            try {
                $this->getFilesystem()->remove([$lazaretFileName, $lazaretThumbFileName]);
            } catch (IOException $e) {
                // No-op
            }
            $ret['success'] = true;
        } catch (\Exception $e) {
            // No-op
        }

        return $ret;
    }

    public function add($file_id, $keepAttributes=true, Array $attributesToKeep=[])
    {
        $ret = ['success' => false, 'message' => ''];

        /* @var LazaretFile $lazaretFile */
        $lazaretFile = $this->getLazaretFileRepository()->find($file_id);

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
                            $metadataBag->set($value->getTag()->getTagname(), new Metadata($value->getTag(), $value->getValue()));
                            break;
                        case AttributeInterface::NAME_STORY:
                            /** @var \record_adapter $value */
                            $value = $attribute->getValue();
                            $value->appendChild($record);
                            break;
                        case AttributeInterface::NAME_STATUS:
                            $record->set_binary_status($attribute->getValue());
                            break;
                        case AttributeInterface::NAME_METAFIELD:
                            /** @var Border\Attribute\MetaField $attribute */
                            $metaFields->set($attribute->getField()->get_name(), $attribute->getValue());
                            break;
                    }
                }

                $data = $metadataBag->toMetadataArray($record->get_databox()->get_meta_structure());
                $record->set_metadatas($data);

                $fields = $metaFields->toMetadataArray($record->get_databox()->get_meta_structure());
                $record->set_metadatas($fields);
            }

            //Delete lazaret file
            $this->manager->remove($lazaretFile);
            $this->manager->flush();

            $ret['success'] = true;
        } catch (\Exception $e) {
            $ret['message'] = $this->app->trans('An error occured');
        }

        try {
            $this->getFilesystem()->remove([$lazaretFileName, $lazaretThumbFileName]);
        } catch (IOException $e) {

        }

        return $ret;
    }

    /**
     * @return LazaretFileRepository
     */
    private function getLazaretFileRepository()
    {
        return $this->app['repo.lazaret-files'];
    }

    /**
     * @return Border\Manager
     */
    private function getBorderManager()
    {
        return $this->app['border-manager'];
    }
}
