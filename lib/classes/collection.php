<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Collection\Collection as CollectionVO;
use Alchemy\Phrasea\Collection\CollectionRepository;
use Alchemy\Phrasea\Collection\CollectionRepositoryRegistry;
use Alchemy\Phrasea\Collection\CollectionService;
use Alchemy\Phrasea\Collection\Reference\CollectionReference;
use Alchemy\Phrasea\Collection\Reference\CollectionReferenceRepository;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvent;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvents;
use Alchemy\Phrasea\Core\Event\Collection\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Collection\NameChangedEvent;
use Alchemy\Phrasea\Core\Thumbnail\ThumbnailedElement;
use Alchemy\Phrasea\Core\Thumbnail\ThumbnailManager;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\File\File;

class collection implements ThumbnailedElement
{

    const PIC_LOGO = 'minilogos';
    const PIC_WM = 'wm';
    const PIC_STAMP = 'stamp';
    const PIC_PRESENTATION = 'presentation';

    private static $_logos = [];
    private static $_stamps = [];
    private static $_watermarks = [];
    private static $_presentations = [];
    private static $_collections = [];

    /**
     * @param Application $app
     * @param $databoxId
     * @return CollectionRepository
     */
    private static function getRepository(Application $app, $databoxId)
    {
        /** @var CollectionRepositoryRegistry $registry */
        $registry = $app['repo.collections-registry'];

        return $registry->getRepositoryByDatabox($databoxId);
    }

    public static function create(Application $app, databox $databox, appbox $appbox, $name, User $user = null)
    {
        $databoxId = $databox->get_sbas_id();

        $repository = self::getRepository($app, $databoxId);
        $collection = new CollectionVO($databoxId, 0, $name);

        $repository->save($collection);

        $repository = $app['repo.collection-references'];
        $collectionReference = new CollectionReference(0, $databoxId, $collection->getCollectionId(), 0, true, '');

        $repository->save($collectionReference);

        $app['repo.collections-registry']->purgeRegistry();

        $collection = new self($app, $collection, $collectionReference);

        if (null !== $user) {
            $collection->collectionService->grantAdminRights($collectionReference, $user);
        }

        $app['dispatcher']->dispatch(CollectionEvents::CREATED, new CreatedEvent($collection));

        return $collection;
    }

    public static function mount_collection(Application $app, databox $databox, $coll_id, User $user)
    {
        $reference = new CollectionReference(0, $databox->get_sbas_id(), $coll_id, 0, true, '');

        $app['repo.collection-references']->save($reference);
        $app['repo.collections-registry']->purgeRegistry();

        $collection = self::getByBaseId($app, $reference->getBaseId());
        $collection->collectionService->grantAdminRights($collection->reference, $user);

        return $reference->getBaseId();
    }

    public static function getLogo($base_id, Application $app, $printname = false)
    {
        $base_id_key = $base_id . '_' . ($printname ? '1' : '0');

        if (!isset(self::$_logos[$base_id_key])) {

            if (is_file($app['root.path'] . '/config/minilogos/' . $base_id)) {
                $name = phrasea::bas_labels($base_id, $app);
                self::$_logos[$base_id_key] = '<img title="' . $name
                    . '" src="/custom/minilogos/' . $base_id . '" />';
            } elseif ($printname) {
                self::$_logos[$base_id_key] = phrasea::bas_labels($base_id, $app);
            }
        }

        return isset(self::$_logos[$base_id_key]) ? self::$_logos[$base_id_key] : '';
    }

    public static function getWatermark($base_id)
    {
        if (!isset(self::$_watermarks['base_id'])) {

            if (is_file(__DIR__ . '/../../config/wm/' . $base_id)) {
                self::$_watermarks['base_id'] = '<img src="/custom/wm/' . $base_id . '" />';
            }
        }

        return isset(self::$_watermarks['base_id']) ? self::$_watermarks['base_id'] : '';
    }

    public static function getPresentation($base_id)
    {
        if (!isset(self::$_presentations['base_id'])) {

            if (is_file(__DIR__ . '/../../config/presentation/' . $base_id)) {
                self::$_presentations['base_id'] = '<img src="/custom/presentation/' . $base_id . '" />';
            }
        }

        return isset(self::$_presentations['base_id']) ? self::$_presentations['base_id'] : '';
    }

    public static function getStamp($base_id)
    {
        if (!isset(self::$_stamps['base_id'])) {

            if (is_file(__DIR__ . '/../../config/stamp/' . $base_id)) {
                self::$_stamps['base_id'] = '<img src="/custom/stamp/' . $base_id . '" />';
            }
        }

        return isset(self::$_stamps['base_id']) ? self::$_stamps['base_id'] : '';
    }

    public static function purge()
    {
        self::$_collections = [];
    }

    /**
     * @param  Application $app
     * @param  int $base_id
     * @return collection
     */
    public static function getByBaseId(Application $app, $base_id)
    {
        /** @var CollectionReferenceRepository $referenceRepository */
        $referenceRepository = $app['repo.collection-references'];
        $reference = $referenceRepository->find($base_id);

        if (!$reference) {
            throw new Exception_Databox_CollectionNotFound(sprintf(
                "Collection with base_id %s could not be found",
                $base_id
            ));
        }

        $repository = self::getRepository($app, $reference->getDataboxId());
        $collection = $repository->find($reference->getCollectionId());

        if (!$collection) {
            throw new Exception_Databox_CollectionNotFound(sprintf(
                "Collection with base_id %s could not be found",
                $base_id
            ));
        }

        if (!$app['conf.restrictions']->isCollectionAvailable($collection)) {
            throw new Exception_Databox_CollectionNotFound(sprintf(
                'Collection `%d` is not available here.',
                $collection->get_base_id()
            ));
        }

        return $collection;
    }

    /**
     * @param  Application $app
     * @param  databox $databox
     * @param  int $coll_id
     * @return collection
     */
    public static function getByCollectionId(Application $app, databox $databox, $coll_id)
    {
        assert(is_int($coll_id));

        $repository = self::getRepository($app, $databox->get_sbas_id());
        $collection = $repository->find($coll_id);

        if (!$collection) {
            throw new Exception_Databox_CollectionNotFound(sprintf(
                "Collection with collection ID %d could not be found",
                $coll_id
            ));
        }

        if (!$app['conf.restrictions']->isCollectionAvailable($collection)) {
            throw new Exception_Databox_CollectionNotFound(sprintf(
                'Collection `%d` is not available here.',
                $collection->get_base_id()
            ));
        }

        return $collection;
    }

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var CollectionService
     */
    protected $collectionService;

    /**
     * @var databox
     */
    protected $databox;

    /**
     * @var CollectionVO
     */
    protected $collectionVO;

    /**
     * @var CollectionReference
     */
    protected $reference;


    /**
     * @param Application $app
     * @param CollectionVO $collection
     * @param CollectionReference $reference
     * @internal param $baseId
     * @internal param array $row
     */
    public function __construct(Application $app, CollectionVO $collection, CollectionReference $reference)
    {
        $this->app = $app;
        $this->databox = $app->getApplicationBox()->get_databox($reference->getDataboxId());
        $this->collectionService = $app->getApplicationBox()->getCollectionService();

        $this->collectionVO = $collection;
        $this->reference = $reference;
    }

    /**
     * @param $eventName
     * @param CollectionEvent $event
     */
    private function dispatch($eventName, CollectionEvent $event)
    {
        $this->app['dispatcher']->dispatch($eventName, $event);
    }

    /**
     * @return CollectionRepository
     */
    private function getCollectionRepository()
    {
        return self::getRepository($this->app, $this->reference->getDataboxId());
    }

    /**
     * @return CollectionReferenceRepository
     */
    private function getReferenceRepository()
    {
        return $this->app['repo.collection-references'];
    }

    public function hydrate(Application $app)
    {
        $this->app = $app;
        $this->databox = $app->getApplicationBox()->get_databox($this->reference->getDataboxId());
        $this->collectionService = $app->getApplicationBox()->getCollectionService();
    }

    public function __sleep()
    {
        return array(
            'collectionVO',
            'reference'
        );
    }

    /**
     * @return CollectionVO
     */
    public function getCollection()
    {
        return $this->collectionVO;
    }

    /**
     * @return CollectionReference
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @return bool
     */
    public function is_active()
    {
        return $this->reference->isActive();
    }

    /**
     *
     * @return databox
     */
    public function get_databox()
    {
        return $this->databox;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    public function get_connection()
    {
        return $this->databox->get_connection();
    }

    /**
     * @param $publi
     * @return $this
     */
    public function set_public_presentation($publi)
    {
        $this->collectionVO->setPublicWatermark($publi);
        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this;
    }

    /**
     * @param $name
     * @return $this
     * @throws Exception_InvalidArgument
     */
    public function set_name($name)
    {
        try {
            $this->collectionVO->setName($name);
        } catch (\InvalidArgumentException $e) {
            throw new Exception_InvalidArgument();
        }

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        $this->dispatch(CollectionEvents::NAME_CHANGED, new NameChangedEvent($this));

        return $this;
    }

    /**
     * @param $code
     * @param $label
     * @return $this
     */
    public function set_label($code, $label)
    {
        $this->collectionVO->setLabel($code, $label);

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this;
    }

    /**
     * @param $code
     * @param bool $substitute
     * @return string
     */
    public function get_label($code, $substitute = true)
    {
        return $this->collectionVO->getLabel($code, $substitute);
    }

    /**
     * @return int
     */
    public function get_ord()
    {
        return $this->reference->getDisplayIndex();
    }

    /**
     * @param $ord
     * @return $this
     */
    public function set_ord($ord)
    {
        $this->reference->setDisplayIndex($ord);

        $this->getReferenceRepository()->save($this->reference);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this;
    }

    /**
     * @return int[]|null|string
     */
    public function get_binary_minilogos()
    {
        return $this->collectionVO->getLogo();
    }

    /**
     * @return int
     */
    public function get_base_id()
    {
        return $this->reference->getBaseId();
    }

    /**
     * @return int
     */
    public function get_sbas_id()
    {
        return $this->reference->getDataboxId();
    }

    /**
     * @return int
     */
    public function get_coll_id()
    {
        return $this->reference->getCollectionId();
    }

    /**
     * @return string
     */
    public function get_prefs()
    {
        return $this->collectionVO->getPreferences();
    }

    /**
     * @param DOMDocument $dom
     * @return string
     */
    public function set_prefs(DOMDocument $dom)
    {
        $this->collectionVO->setPreferences($dom->saveXML());

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this->collectionVO->getPreferences();
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return $this->collectionVO->getName();
    }

    /**
     * @return string
     */
    public function get_pub_wm()
    {
        return $this->collectionVO->getName();
    }

    /**
     * @return bool
     */
    public function is_available()
    {
        return true;
    }

    /**
     * @return int
     */
    public function getRootIdentifier()
    {
        return $this->reference->getBaseId();
    }

    /**
     * @return $this
     */
    public function disable()
    {
        $this->reference->disable();

        $this->getReferenceRepository()->save($this->reference);
        $this->app['repo.collections-registry']->purgeRegistry();

        cache_databox::update($this->app, $this->databox->get_sbas_id(), 'structure');

        return $this;
    }

    /**
     * @return $this
     */
    public function enable()
    {
        $this->reference->enable();

        $this->getReferenceRepository()->save($this->reference);
        $this->app['repo.collections-registry']->purgeRegistry();

        cache_databox::update($this->app, $this->databox->get_sbas_id(), 'structure');

        return $this;
    }

    /**
     * @param int $pass_quantity
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function empty_collection($pass_quantity = 100)
    {
        $this->collectionService->emptyCollection($this->databox, $this->collectionVO, $pass_quantity);

        return $this;
    }

    /**
     * @param string $thumbnailType
     * @param File $file
     */
    public function updateThumbnail($thumbnailType, File $file = null)
    {
        switch ($thumbnailType) {
            case ThumbnailManager::TYPE_WM;
                $this->reset_watermark();
                break;
            case ThumbnailManager::TYPE_LOGO:
                $this->update_logo($file);
                break;
            case ThumbnailManager::TYPE_PRESENTATION:
                break;
            case ThumbnailManager::TYPE_STAMP:
                $this->reset_stamp();
                break;
            default:
                throw new \InvalidArgumentException('Unsupported thumbnail type.');
        }
    }

    /**
     * @return int|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function get_record_amount()
    {
        return $this->collectionService->getRecordCount($this->collectionVO);
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function get_record_details()
    {
        return $this->collectionService->getRecordDetails($this->collectionVO);
    }

    /**
     * @param SplFileInfo $pathfile
     * @return $this
     */
    public function update_logo(\SplFileInfo $pathfile = null)
    {
        $fileContents = null;

        if (!is_null($pathfile)) {
            $fileContents = file_get_contents($pathfile->getPathname());
        }

        $this->collectionVO->setLogo($fileContents);

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this;
    }

    /**
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function reset_watermark()
    {
        $this->collectionService->resetWatermark($this->collectionVO);

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this;
    }

    /**
     * @param null $record_id
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function reset_stamp($record_id = null)
    {
        $this->collectionService->resetStamp($this->collectionVO, $record_id);

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public function delete()
    {
        $this->collectionService->delete($this->databox, $this->collectionVO, $this->reference);

        $this->getCollectionRepository()->delete($this->collectionVO);

        $this->app['manipulator.registration']->deleteRegistrationsOnCollection($this);
        $this->app['repo.collections-registry']->purgeRegistry();
    }

    /**
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unmount()
    {
        $this->collectionService->unmountCollection($this->reference);

        $this->getReferenceRepository()->delete($this->reference);

        $this->app['manipulator.registration']->deleteRegistrationsOnCollection($this);
        $this->app['repo.collections-registry']->purgeRegistry();

        return $this;
    }

    /**
     * Tells whether registration is activated for provided collection or not.
     *
     * @return boolean
     */
    public function isRegistrationEnabled()
    {
        if (false === $xml = simplexml_load_string($this->get_prefs())) {
            return false;
        }

        $element = $xml->xpath('/baseprefs/caninscript');

        if (count($element) === 0) {
            return $this->databox->isRegistrationEnabled();
        }

        foreach ($element as $caninscript) {
            if (false !== (bool)(string)$caninscript) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets terms of use.
     *
     * @return null|string
     */
    public function getTermsOfUse()
    {
        if (false === $xml = simplexml_load_string($this->get_prefs())) {
            return null;
        }

        foreach ($xml->xpath('/baseprefs/cgu') as $sbpcgu) {
            return $sbpcgu->saveXML();
        }
    }
}
