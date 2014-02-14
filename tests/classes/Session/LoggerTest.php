<?php

class Session_LoggerTest extends \PhraseanetTestCase
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

    private function feed_datas()
    {
        $user = self::$DI['user'];

        $this->authenticate(self::$DI['app']);
        $logger_creater = self::$DI['app']['phraseanet.logger'];

        foreach (self::$DI['app']['acl']->get($user)->get_granted_sbas() as $databox) {
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

        $sql = 'SELECT id FROM log
            WHERE sit_session = :ses_id AND usrid = :usr_id AND site = :site';
        $params = [
            ':ses_id' => self::$DI['app']['session']->get('session_id')
            , ':usr_id' => self::$DI['app']['authentication']->getUser()->get_id()
            , ':site'   => self::$DI['app']['conf']->get(['main', 'key'])
        ];

        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $this->assertEquals(1, $stmt->rowCount());
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($this->object->get_id(), $row['id']);
        $log_id = $this->object->get_id();
        $ses_id = self::$DI['app']['session']->get('session_id');
        $usr_id = self::$DI['app']['authentication']->getUser()->get_id();

        $this->logout(self::$DI['app']);

        $sql = 'SELECT id FROM log
            WHERE sit_session = :ses_id AND usrid = :usr_id AND site = :site';
        $params = [
            ':ses_id' => $ses_id
            , ':usr_id' => $usr_id
            , ':site'   => self::$DI['app']['conf']->get(['main', 'key'])
        ];

        $stmt = $this->databox->get_connection()->prepare($sql);
        $stmt->execute($params);
        $this->assertEquals(1, $stmt->rowCount());
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($log_id, $row['id']);
    }
}
