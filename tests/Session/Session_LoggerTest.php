<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class Session_LoggerTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var Session_Logger
     */
    protected $object;

    /**
     *
     * @var databox
     */
    protected $databox;

    protected function feed_datas()
    {
        $user = self::$user;
        $auth = new Session_Authentication_None($user);

        self::$application->openAccount($auth);
        $logger_creater = self::$application['phraseanet.logger'];

        foreach ($user->ACL()->get_granted_sbas() as $databox) {
            $this->object = $logger_creater($databox);
            $this->databox = $databox;
            break;
        }
        if ( ! $this->object instanceof Session_Logger)
            $this->fail('Not enough datas to test');
    }

    public function testGet_id()
    {
        $this->feed_datas();
        $log_id = $this->object->get_id();
        $this->assertTrue(is_int($log_id));

        $registry = self::$application['phraseanet.registry'];

        $sql = 'SELECT id FROM log
            WHERE sit_session = :ses_id AND usrid = :usr_id AND site = :site';
        $params = array(
            ':ses_id' => self::$application['session']->get('phrasea_session_id')
            , ':usr_id' => self::$application['phraseanet.user']->get_id()
            , ':site'   => $registry->get('GV_sit')
        );

        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $this->assertEquals(1, $stmt->rowCount());
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($this->object->get_id(), $row['id']);
        $log_id = $this->object->get_id();
        $ses_id = self::$application['session']->get('phrasea_session_id');
        $usr_id = self::$application['phraseanet.user']->get_id();

        self::$application->closeAccount();

        $sql = 'SELECT id FROM log
            WHERE sit_session = :ses_id AND usrid = :usr_id AND site = :site';
        $params = array(
            ':ses_id' => $ses_id
            , ':usr_id' => $usr_id
            , ':site'   => $registry->get('GV_sit')
        );

        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $this->assertEquals(1, $stmt->rowCount());
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($log_id, $row['id']);
    }
}
