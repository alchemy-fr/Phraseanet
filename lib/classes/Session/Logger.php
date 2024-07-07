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
use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Model\Entities\SessionModule;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;

class Session_Logger
{
    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var databox
     */
    protected $databox;

    const EVENT_DELETE = 'delete';
    const EVENT_EDIT = 'edit';
    const EVENT_EXPORTDOWNLOAD = 'download';
    const EVENT_EXPORTFTP = 'ftp';
    const EVENT_EXPORTMAIL = 'mail';
    const EVENT_MOVE = 'collection';
    const EVENT_PRINT = 'print';
    const EVENT_PUSH = 'push';
    const EVENT_STATUS = 'status';
    const EVENT_SUBSTITUTE = 'substit';
    const EVENT_VALIDATE = 'validate';
    const EVENT_SUBDEFCREATION = 'subdefCreation';
    const EVENT_WRITEMETADATAS = 'writeMetadatas';

    /**
     *
     * @param databox     $databox
     * @param integer     $log_id
     *
     * @return Session_Logger
     */
    public function __construct(databox $databox, $log_id)
    {
        $this->databox = $databox;
        $this->id = (int) $log_id;

        return $this;
    }

    /**
     *
     * @return int
     */
    public function get_id()
    {
        return $this->id;
    }

    public function log(record_adapter $record, $action, $final, $comment, $coll_id_from = null, DateTime $date = null)
    {
        $sql = 'INSERT INTO log_docs
          (id, log_id, date, record_id, coll_id_from, coll_id, action, final, comment)
          VALUES (null, :log_id, :date, :record_id, :coll_id_from, :coll_id, :action, :final, :comm)';

        $stmt = $this->databox->get_connection()->prepare($sql);

        $params = [
            ':log_id'    => $this->get_id(),
            ':date'      => ($date == null ) ? (new \DateTime('now'))->format(DATE_ATOM) : $date->format(DATE_ATOM),
            ':record_id' => $record->getRecordId(),
            ':coll_id_from' => $coll_id_from,
            ':coll_id' => $record->getCollectionId(),
            ':action'    => $action,
            ':final' => $final,
            ':comm' => $comment,
        ];

        $stmt->execute($params);
        $stmt->closeCursor();

        return $this;
    }

    /**
     *
     * @param Application $app
     * @param databox     $databox
     * @param Browser     $browser
     *
     * @return Session_Logger
     */
    public static function create(Application $app, databox $databox, Browser $browser)
    {
        $colls = [];

        if ($app->getAuthenticatedUser()) {
            $bases = $app->getAclForUser($app->getAuthenticatedUser())->get_granted_base([], [$databox->get_sbas_id()]);
            foreach ($bases as $collection) {
                $colls[] = $collection->get_coll_id();
            }
        }

        $conn =  $databox->get_connection();

        $sql = "INSERT INTO log
              (id, date,sit_session, user, site, usrid, nav,
                version, os, res, ip, user_agent,appli, fonction,
                societe, activite, pays)
            VALUES
              (null,now() , :ses_id, :usr_login, :site_id, :usr_id
              , :browser, :browser_version,  :platform, :screen, :ip
              , :user_agent, :appli, :fonction, :company, :activity, :country)";

        $params = [
            ':ses_id'          => $app['session']->get('session_id'),
            ':usr_login'       => $app->getAuthenticatedUser() ? $app->getAuthenticatedUser()->getLogin() : null,
            ':site_id'         => $app['conf']->get(['main', 'key']),
            ':usr_id'          => $app->getAuthenticator()->isAuthenticated() ? $app->getAuthenticatedUser()->getId() : null,
            ':browser'         => $browser->getBrowser(),
            ':browser_version' => $browser->getExtendedVersion(),
            ':platform'        => $browser->getPlatform(),
            ':screen'          => $browser->getScreenSize(),
            ':ip'              => $browser->getIP(),
            ':user_agent'      => $browser->getUserAgent(),
            ':appli'           => serialize([]),
            ':fonction' => $app->getAuthenticatedUser() ? $app->getAuthenticatedUser()->getJob() : null,
            ':company'  => $app->getAuthenticatedUser() ? $app->getAuthenticatedUser()->getCompany() : null,
            ':activity' => $app->getAuthenticatedUser() ? $app->getAuthenticatedUser()->getActivity() : null,
            ':country'  => $app->getAuthenticatedUser() ? $app->getAuthenticatedUser()->getCountry() : null
        ];

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $log_id = $conn->lastInsertId();
        $stmt->closeCursor();

        unset($stmt, $conn);

        return new Session_Logger($databox, $log_id);
    }

    public static function load(Application $app, databox $databox)
    {
        if ( ! $app->getAuthenticator()->isAuthenticated()) {
            throw new Exception_Session_LoggerNotFound('Not authenticated');
        }

        $sql = 'SELECT id FROM log
            WHERE site = :site AND sit_session = :ses_id';

        $params = [
            ':site'   => $app['conf']->get(['main', 'key'])
            , ':ses_id' => $app['session']->get('session_id')
        ];

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_Session_LoggerNotFound('Logger not found');

        return new self($databox, $row['id']);
    }

    public static function loadById($databox, $logId)
    {
        $sql = 'SELECT id, site FROM log
            WHERE id = :log_id ';

        $params = [
            ':log_id'   => $logId
        ];

        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            throw new Exception_Session_LoggerNotFound('Logger not found');
        }

        return new self($databox, $logId);
    }

    public static function updateClientInfos(Application $app, $appId)
    {
        if (!$app->getAuthenticator()->isAuthenticated()) {
            return;
        }

        $session = $app['repo.sessions']->find($app['session']->get('session_id'));

        if (!$session) {
            throw new SessionNotFound('No session found');
        }

        if (!$session->hasModuleId($appId)) {
            $module = new SessionModule();

            $module->setModuleId($appId);
            $module->setSession($session);
            $session->addModule($module);

            $app['orm.em']->persist($module);
            $app['orm.em']->persist($session);

            $app['orm.em']->flush();
        }

        $appName = [
            '1' => 'Prod',
            '2' => 'Client',
            '3' => 'Admin',
            '4' => 'Report',
            '5' => 'Thesaurus',
            '6' => 'Compare',
            '7' => 'Validate',
            '8' => 'Upload',
            '9' => 'API'
        ];

        if (isset($appName[$appId])) {
            $sbas_ids = array_keys($app->getAclForUser($app->getAuthenticatedUser())->get_granted_sbas());

            foreach ($sbas_ids as $sbas_id) {
                try {
                    $logger = $app['phraseanet.logger']($app->findDataboxById($sbas_id));

                    $databox = $app->findDataboxById($sbas_id);
                    $connbas = $databox->get_connection();
                    $sql = 'SELECT appli FROM log WHERE id = :log_id';
                    $stmt = $connbas->prepare($sql);
                    $stmt->execute([':log_id' => $logger->get_id()]);
                    $row3 = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stmt->closeCursor();

                    if (!$row3)
                        throw new Exception('no log');
                    $applis = unserialize($row3['appli']);

                    if (!in_array($appId, $applis)) {
                        $applis[] = $appId;
                    }

                    $sql = 'UPDATE log SET appli = :applis WHERE id = :log_id';

                    $params = [
                        ':applis' => serialize($applis)
                        , ':log_id' => $logger->get_id()
                    ];

                    $stmt = $connbas->prepare($sql);
                    $stmt->execute($params);
                    $stmt->closeCursor();
                } catch (\Exception $e) {

                }
            }
        }

        return;
    }

    public function initOrUpdateLogDocsFromWorker(\record_adapter $record, \databox $databox, WorkerRunningJob $workerRunningJob, $subdefName, $action, DateTime $finished = null, $status = WorkerRunningJob::RUNNING)
    {
        $whereClause = ' date=:date AND record_id=:record_id AND action=:action AND final=:final';

        $sqlCount = 'SELECT COUNT(id) as n FROM log_docs WHERE ' . $whereClause;

        $params = [
            ':date'        => $workerRunningJob->getCreated()->format('Y-m-d H:i:s'),
            ':record_id'   => $record->getRecordId(),
            ':action'      => $workerRunningJob->getWork(),
            ':final'       => $subdefName
        ];

        $stmt = $databox->get_connection()->prepare($sqlCount);
        $stmt->execute($params);
        $count = $stmt->fetchColumn(0);
        $stmt->closeCursor();

        $comment = json_encode([
            'finished' => empty($finished) ? '' : $finished->format('Y-m-d H:i:s'),
            'duration' => empty($finished) ? '' : $finished->getTimestamp() - $workerRunningJob->getCreated()->getTimestamp() ,
            'status'   => $status,
            'subdefName' => $subdefName
        ]);

        if ($count > 0) {
            $sql = "UPDATE log_docs SET comment=:comment WHERE " . $whereClause;
            $stmt = $databox->get_connection()->prepare($sql);

            $p = [
                ':comment' =>  $comment,
                ':date'        => $workerRunningJob->getCreated()->format('Y-m-d H:i:s'),
                ':record_id'   => $record->getRecordId(),
                ':action'      => $workerRunningJob->getWork(),
                ':final'       => $subdefName
            ];

            $stmt->execute($p);
            $stmt->closeCursor();
        } else {
            // insert to log_docs
            $this->log(
                $record,
                $action,
                $subdefName,
                $comment,
                null,
                $workerRunningJob->getCreated()
            );
        }
    }
}
