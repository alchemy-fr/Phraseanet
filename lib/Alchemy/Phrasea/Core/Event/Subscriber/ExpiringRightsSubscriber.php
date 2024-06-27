<?php

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Core\Event\ExportEvent;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use appbox;
use databox;
use Doctrine\DBAL\DBALException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpiringRightsSubscriber implements EventSubscriberInterface
{
    use DataboxLoggerAware;

    /** @var  Application */
    private $app;

    /**
     * @var callable
     */
    private $appboxLocator;

    public function __construct(Application $app, callable $appboxLocator)
    {
        $this->app                 = $app;
        $this->appboxLocator       = $appboxLocator;
    }

    private function getJobsByDb($sbas_id)
    {
        static $jobsByDb = [];
        static $jobsByName = null;

        if($jobsByName === null) {
            $jobsByName = [];
            foreach ($this->app['conf']->get(['expiring-rights', 'jobs'], []) as $jobname => &$job) {
                // nb: we must include inactive jobs so every download is recorded
                $job['_c'] = $job['collection'];
                unset($job['collection']);
                $jobsByName[$jobname] = $job;
            }
        }

        $sbas_id = (int)$sbas_id;
        if(!array_key_exists($sbas_id, $jobsByDb)) {
            $jobsByDb[$sbas_id] = [];
            /** @var appbox $abox */
            $abox = $this->app['phraseanet.appbox'];
            $dbox = $abox->get_databox($sbas_id);
            foreach($jobsByName as $jobname => &$job) {
                if($job['databox'] === $sbas_id || $job['databox'] === $dbox->get_dbname()) {
                    // patch the collections filter to have id's and names
                    if(!array_key_exists('collection', $job)) {
                        $job['collection'] = [];
                        foreach ($dbox->get_collections() as $coll) {
                            if(empty($job['_c']) ||
                                in_array($coll->get_coll_id(), $job['_c'], true) ||
                                in_array($coll->get_name(), $job['_c'], true))
                            {
                                $job['collection'][] = $coll->get_coll_id();
                                $job['collection'][] = $coll->get_name();
                            }
                        }
                    }
                    $jobsByDb[$sbas_id][$jobname] = &$jobsByName[$jobname];
                }
            }
        }

        return array_key_exists($sbas_id, $jobsByDb) ? $jobsByDb[$sbas_id] : [];
    }

    /**
     * when someone downloads, if the base has a declared "expire_field"
     * insert an entry in our table
     *
     * @param ExportEvent $event
     * @param string $eventName
     */
    public function onExportCreate(ExportEvent $event, $eventName)
    {
        $user_id = $event->getUser()->getId();
        $email = $event->getUser()->getEmail();
        /** @var appbox $abox */
        $abox = $this->getApplicationBox();
        $stmt = null;

        foreach(explode(';', $event->getList()) as $sbid_rid) {
            list($sbas_id, $record_id) = explode('_', $sbid_rid);

            try {
                /** @var databox $dbox */
                $dbox = $abox->get_databox($sbas_id);
            }
            catch (\Exception $e) {
                continue;
            }

            foreach($this->getJobsByDb($sbas_id) as $jobname => $job) {
                if($job['target'] !== "downloaders") {
                    continue;
                }

                $record = $dbox->get_record($record_id);

                // get the expire_field unique value
                $expire_value = null;
                $expire_field = $job['expire_field'];
                $fields = $record->getCaption([$expire_field]);
                if (array_key_exists($expire_field, $fields) && count($fields[$expire_field]) === 1) {
                    try {
                        $expire_value = (new \DateTime($fields[$expire_field][0]))->format('Y-m-d');   // drop hour, minutes...
                    }
                    catch (\Exception $e) {
                        // bad date format ? set null
                    }
                }

                try {
                    // insert
                    if(!$stmt) {
                        // first sql
                        $sql = "INSERT INTO `ExpiringRights` (job, downloaded, user_id, email, sbas_id, base, collection, record_id, title, expire)\n"
                            . " VALUES (:job, NOW(), :user_id, :email, :sbas_id, :base, :collection, :record_id, :title, :expire)";
                        $stmt = $abox->get_connection()->prepare($sql);
                    }
                    try {
                        $stmt->execute([
                            ':job'        => $jobname,
                            ':user_id'    => $user_id,
                            ':email'      => $email,
                            ':sbas_id'    => $sbas_id,
                            ':base'       => $dbox->get_viewname(),   // fallback of get_label() because we may not know the current language
                            ':collection' => $record->getCollection()->get_name(),
                            ':record_id'  => $record_id,
                            ':title'      => $record->get_title(),
                            ':expire'     => $expire_value
                        ]);
                    }
                    catch (\Exception $e) {
                        // duplicate (same job+user+sbas_id+record_id) ?
                    }
                }
                catch (\Exception $e) {
                    // no-op
                }

            }
        }
        if($stmt) {
            $stmt->closeCursor();
        }
    }

    /**
     * when the "expire_field" is edited, update the new value for every downloaded record
     *
     * @param RecordEdit $event
     * @param string $eventName
     * @throws DBALException
     */
    public function onRecordEdit(RecordEdit $event, $eventName)
    {
        $record = $event->getRecord();
        $sbas_id = $record->getDataboxId();

        // get settings for this databox
        foreach($this->getJobsByDb($sbas_id) as $job) {
            if($job['target'] !== "downloaders") {
                continue;
            }
            $expire_field = $job['expire_field'];
            $expire_value = null;
            $record_id = $record->getRecordId();
            $fields = $record->getCaption([$expire_field]);
            if (array_key_exists($expire_field, $fields) && count($fields[$expire_field]) > 0) {
                $expire_value = $fields[$expire_field][0];
            }

            /** @var appbox $abox */
            $abox = $this->getApplicationBox();
            $sql = "UPDATE `ExpiringRights` SET new_expire = :new_expire\n"
                . " WHERE sbas_id = :sbas_id AND record_id = :record_id AND (IFNULL(expire, 0) != IFNULL(:expire, 0) OR !ISNULL(new_expire))";
            $stmt = $abox->get_connection()->prepare($sql);
            $stmt->execute($p = [
                ':new_expire' => $expire_value,
                ':sbas_id'    => $sbas_id,
                ':record_id'  => $record_id,
                ':expire'     => $expire_value
            ]);
            $stmt->closeCursor();
        }
    }


    public static function getSubscribedEvents()
    {
        return [
            /** @uses onRecordEdit */
            PhraseaEvents::RECORD_EDIT => 'onRecordEdit',
            /** @uses onExportCreate */
            PhraseaEvents::EXPORT_CREATE   => 'onExportCreate',
        ];
    }


    /**
     * @return appbox
     */
    private function getApplicationBox()
    {
        static $appbox = null;
        if($appbox === null) {
            $callable = $this->appboxLocator;
            $appbox = $callable();
        }
        return $appbox;
    }
}
