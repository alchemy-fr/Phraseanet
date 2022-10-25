<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
use Alchemy\Phrasea\Core\Thumbnail\ThumbnailedElement;
use Alchemy\Phrasea\Core\Thumbnail\ThumbnailManager;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\File\File;

use Alchemy\Phrasea\Core\Event\Collection\CollectionEvent;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvents;
use Alchemy\Phrasea\Core\Event\Collection\CreatedEvent;
use Alchemy\Phrasea\Core\Event\Collection\NameChangedEvent;
use Alchemy\Phrasea\Core\Event\Collection\EmptiedEvent;
use Alchemy\Phrasea\Core\Event\Collection\EnabledEvent;
use Alchemy\Phrasea\Core\Event\Collection\DisabledEvent;
use Alchemy\Phrasea\Core\Event\Collection\MountedEvent;
use Alchemy\Phrasea\Core\Event\Collection\UnmountedEvent;
use Alchemy\Phrasea\Core\Event\Collection\SettingsChangedEvent;
use Alchemy\Phrasea\Core\Event\Collection\LabelChangedEvent;

class collection implements ThumbnailedElement, cache_cacheableInterface
{

    const PIC_LOGO = 'minilogos';
    const PIC_WM = 'wm';
    const PIC_STAMP = 'stamp';
    const PIC_PRESENTATION = 'presentation';

    private static $_logos = [];
    private static $_stamps = [];
    private static $_watermarks = [];
    private static $_presentations = [];

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

        $app['dispatcher']->dispatch(
            CollectionEvents::CREATED,
            new CreatedEvent(
                $collection
            )
        );

        return $collection;
    }

    public static function mount_collection(Application $app, databox $databox, $coll_id, User $user)
    {
        $reference = new CollectionReference(0, $databox->get_sbas_id(), $coll_id, 0, true, '');

        $app['repo.collection-references']->save($reference);
        $app['repo.collections-registry']->purgeRegistry();

        $collection = self::getByBaseId($app, $reference->getBaseId());
        $collection->collectionService->grantAdminRights($collection->reference, $user);

        $app['dispatcher']->dispatch(
            CollectionEvents::MOUNTED,
            new MountedEvent(
                $collection
            )
        );

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
        // BC only
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

        return self::getAvailableCollection($app, $reference->getDataboxId(), $reference->getCollectionId());
    }

    /**
     * @param Application $app
     * @param databox|int $databox
     * @param int $collectionId
     * @return collection
     */
    public static function getByCollectionId(Application $app, $databox, $collectionId)
    {
        assert(is_int($collectionId));
        $databoxId = $databox instanceof databox ? $databox->get_sbas_id() : (int)$databox;

        return self::getAvailableCollection($app, $databoxId, $collectionId);
    }

    /**
     * @param Application $app
     * @return \Alchemy\Phrasea\Core\Configuration\AccessRestriction
     */
    private static function getAccessRestriction(Application $app)
    {
        return $app['conf.restrictions'];
    }

    private static function assertCollectionIsAvailable(Application $app, collection $collection)
    {
        if (!self::getAccessRestriction($app)->isCollectionAvailable($collection)) {
            throw new Exception_Databox_CollectionNotFound(sprintf(
                'Collection `%d` is not available here.',
                $collection->get_base_id()
            ));
        }
    }

    /**
     * @param Application $app
     * @param int $databoxId
     * @param int $collectionId
     * @return collection
     */
    private static function getByDataboxIdAndCollectionId(Application $app, $databoxId, $collectionId)
    {
        $repository = self::getRepository($app, $databoxId);
        $collection = $repository->find($collectionId);

        if (!$collection) {
            throw new Exception_Databox_CollectionNotFound(sprintf(
                "Collection '%d' on databox '%d' could not be found",
                $collectionId,
                $databoxId
            ));
        }

        return $collection;
    }

    /**
     * @param Application $app
     * @param int $databoxId
     * @param int $collectionId
     * @return collection
     */
    private static function getAvailableCollection(Application $app, $databoxId, $collectionId)
    {
        $collection = self::getByDataboxIdAndCollectionId($app, $databoxId, $collectionId);
        self::assertCollectionIsAvailable($app, $collection);

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
     * @var CollectionRepositoryRegistry
     */
    protected $collectionRepositoryRegistry;

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
        $this->collectionVO = $collection;
        $this->reference = $reference;

        $this->fetchInternalServices($app);
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
        $this->fetchInternalServices($app);
    }

    public function __sleep()
    {
        return [
            'collectionVO',
            'reference'
        ];
    }

    public function __debugInfo()
    {
        return [
            'reference' => $this->reference,
            'databox' => $this->databox,
            'collectionVO' => $this->collectionVO
        ];
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
        $old_name = $this->get_name();

        try {
            $this->collectionVO->setName($name);
        } catch (\InvalidArgumentException $e) {
            throw new Exception_InvalidArgument();
        }

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        $this->dispatch(CollectionEvents::NAME_CHANGED,
            new NameChangedEvent(
                $this,
                array("name_before"=>$old_name)
            )
        );

        return $this;
    }

    /**
     * @param $code
     * @param $label
     * @return $this
     */
    public function set_label($code, $label)
    {
        $old_label = $this->collectionVO->getLabel($code);

        $this->collectionVO->setLabel($code, $label);

        $this->getCollectionRepository()->save($this->collectionVO);
        $this->app['repo.collections-registry']->purgeRegistry();

        $this->dispatch(CollectionEvents::LABEL_CHANGED, new LabelChangedEvent($this, array(
            "lng"=>$code,
            "label_before"=>$old_label,
        )));

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
        return (int) $this->reference->getBaseId();
    }

    /**
     * @return int
     */
    public function get_sbas_id()
    {
        return (int) $this->reference->getDataboxId();
    }

    /**
     * @return int
     */
    public function get_coll_id()
    {
        return (int) $this->reference->getCollectionId();
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
        $oldPreferences = $this->collectionVO->getPreferences();

        $this->collectionVO->setPreferences($dom->saveXML());
        $this->getCollectionRepository()->save($this->collectionVO);

        $this->app['repo.collections-registry']->purgeRegistry();

        $this->dispatch(
            CollectionEvents::SETTINGS_CHANGED,
            new SettingsChangedEvent(
                $this,
                array(
                    'settings_before' => $oldPreferences
                )
            )
        );

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
        return $this->collectionVO->getPublicWatermark();
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
        $this->collectionRepositoryRegistry->purgeRegistry();

        // clear cached collection
        $this->getCollectionRepository()->clearCache();

        // clear the trivial cache of databox->get_collections()
        $this->get_databox()->clearCache(databox::CACHE_COLLECTIONS);

        cache_databox::update($this->app, $this->databox->get_sbas_id(), 'structure');
        
	    $this->dispatch(CollectionEvents::DISABLED, new DisabledEvent($this));
	
        return $this;
    }

    /**
     * @return $this
     */
    public function enable()
    {
        $this->reference->enable();

        $this->getReferenceRepository()->save($this->reference);
        $this->collectionRepositoryRegistry->purgeRegistry();

        // clear cached collection
        $this->getCollectionRepository()->clearCache();

        // clear the trivial cache of databox->get_collections()
        $this->get_databox()->clearCache(databox::CACHE_COLLECTIONS);

        cache_databox::update($this->app, $this->databox->get_sbas_id(), 'structure');
        
	    $this->dispatch(CollectionEvents::ENABLED, new EnabledEvent($this));
	
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
        $this->dispatch(CollectionEvents::EMPTIED, new EmptiedEvent($this));
        return $this;
    }

    public function getCollectionRecordIdList()
    {
        return $this->collectionService->getCollectionRecordIdList($this->collectionVO);
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
        $this->collectionRepositoryRegistry->purgeRegistry();

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
        $this->collectionRepositoryRegistry->purgeRegistry();

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
        $this->collectionRepositoryRegistry->purgeRegistry();

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
        $this->collectionRepositoryRegistry->purgeRegistry();
    }

    /**
     * @return $this
     * @throws \Doctrine\DBAL\DBALException
     */
    public function unmount()
    {
        $old_coll_id = $this->get_coll_id();
        $old_name = $this->get_name();

        $this->collectionService->unmountCollection($this->reference);

        $this->getReferenceRepository()->delete($this->reference);

        $this->app['manipulator.registration']->deleteRegistrationsOnCollection($this);
        $this->collectionRepositoryRegistry->purgeRegistry();

        $this->dispatch(
            CollectionEvents::UNMOUNTED,
            new UnmountedEvent(
                null,                   // the coll is not available anymore
                array(
                    'coll_id' => $old_coll_id,
                    'coll_name' => $old_name
                )
            )
        );

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
     * matches a email against the auto-register whitelist
     *
     * @param string $email
     * @return null|string  the user-model to apply if the email matches
     */
    public function getAutoregisterModel($email)
    {
        // try to match against the collection whitelist
        if($this->isRegistrationEnabled()) {
            if (($xml = @simplexml_load_string($this->get_prefs())) !== false) {
                foreach ($xml->xpath('/baseprefs/registration/auto_register/email_whitelist/email') as $element) {
                    if (preg_match($element['pattern'], $email) === 1) {
                        return (string)$element['user_model'];
                    }
                }
            }
        }

        // no match ? try against the databox whitelist
        return $this->get_databox()->getAutoregisterModel($email);
    }

    public function get_cache_key($option = null)
    {
        return 'collection_' . $this->collectionVO->getCollectionId() . ($option ? '_' . $option : '');
    }

    public function get_data_from_cache($option = null)
    {
        return $this->databox->get_data_from_cache($this->get_cache_key($option));
    }

    public function set_data_to_cache($value, $option = null, $duration = 0)
    {
        return $this->databox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
    }

    public function delete_data_from_cache($option = null)
    {
        $this->databox->delete_data_from_cache($this->get_cache_key($option));
    }

    /**
     * @param Application $app
     */
    private function fetchInternalServices(Application $app)
    {
        $this->app = $app;
        $this->databox = $app->getApplicationBox()->get_databox($this->reference->getDataboxId());
        $this->collectionService = $app->getApplicationBox()->getCollectionService();
        $this->collectionRepositoryRegistry = $app['repo.collections-registry'];
    }
}
