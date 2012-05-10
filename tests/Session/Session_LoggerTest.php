<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class Session_LoggerTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var Session_Logger
     */
    protected $object;

    /**
     *
     * @var Session_Handler
     */
    protected $session;

    /**
     *
     * @var databox
     */
    protected $databox;

    protected function feed_datas()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $this->session = $appbox->get_session();
        $user = self::$user;
        $auth = new Session_Authentication_None($user);



        $this->session->authenticate($auth);

        foreach ($user->ACL()->get_granted_sbas() as $databox) {
            $this->object = $this->session->get_logger($databox);
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

        $registry = registry::get_instance();

        $sql = 'SELECT id FROM log
            WHERE sit_session = :ses_id AND usrid = :usr_id AND site = :site';
        $params = array(
            ':ses_id' => $this->session->get_ses_id()
            , ':usr_id' => $this->session->get_usr_id()
            , ':site'   => $registry->get('GV_sit')
        );

        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $this->assertEquals(1, $stmt->rowCount());
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($this->object->get_id(), $row['id']);
        $log_id = $this->object->get_id();
        $ses_id = $this->session->get_ses_id();
        $usr_id = $this->session->get_usr_id();

        $this->session->logout();

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
