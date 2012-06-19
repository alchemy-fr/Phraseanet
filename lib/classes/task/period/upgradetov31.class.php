<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_upgradetov31 extends task_abstract
{

    // ==========================================================================
    // ===== les interfaces de settings (task2.php) pour ce type de tache
    // ==========================================================================
    // ====================================================================
    // getName() : must return the name of this kind of task (utf8), MANDATORY
    // ====================================================================
    public function getName()
    {
        return(_("upgrade to v3.1"));
    }

    public static function interfaceAvailable()
    {
        return false;
    }

    public function help()
    {
        return(utf8_encode("Upgrade some database values"));
    }

    protected function run2()
    {
        printf("taskid %s starting." . PHP_EOL, $this->getID());
        // task can't be stopped here
        $core = \bootstrap::getCore();
        $appbox = appbox::get_instance($core);
        $conn = $appbox->get_connection();
        $running = true;

        $todo = $this->how_many_left();
        $done = 0;

        $appbox = appbox::get_instance(\bootstrap::getCore());
        $ret = 'stopped';

        $this->setProgress($done, $todo);


        while ($running) {

            foreach ($appbox->get_databoxes() as $databox) {
                $connbas = $databox->get_connection();

                $sql = 'SELECT r.coll_id, r.type, r.record_id, s.path, s.file, r.xml
                FROM record r, subdef s
                        WHERE ISNULL(uuid)
                        AND s.record_id = r.record_id AND s.name="document" LIMIT 100';

                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                foreach ($rs as $row) {
                    $pathfile = p4string::addEndSlash($row['path']) . $row['file'];

                    $uuid = uuid::generate_v4();
                    try {
                        $media = $core->guess(new \SplFileInfo($pathfile));
                        $collection = \collection::get_from_coll_id($databox, $row['coll_id']);

                        $file = new \Alchemy\Phrasea\Border\File($media, $collection);
                        $uuid = $file->getUUID(true, true);
                    } catch (\Exception $e) {

                    }

                    $sql = 'UPDATE record SET uuid = :uuid WHERE record_id = :record_id';

                    $params = array(
                        ':uuid'      => $uuid
                        , ':record_id' => $row['record_id']
                    );
                    $stmt = $connbas->prepare($sql);
                    $stmt->execute($params);
                    $stmt->closeCursor();

                    $this->log("mise a jour du record " . $row['record_id'] . " avec uuid " . $uuid);

                    $done ++;
                    $this->setProgress($done, $todo);
                }
            }

            $todo = $this->how_many_left() + $done;

            if ($done == $todo) {
                $sql = 'UPDATE task2 SET status="tostop" WHERE  task_id = :task_id';
                $stmt = $conn->prepare($sql);
                $stmt->execute(array(':task_id' => $this->getID()));
                $stmt->closeCursor();

                $this->setProgress(0, 0);
                $ret = 'todelete';
            }

            $sql = "SELECT status FROM task2 WHERE status='tostop' AND task_id=" . $this->getID();
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':task_id' => $this->getID()));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if ($row) {
                $running = false;
            }

            $conn->close();
            unset($conn);
            sleep(1);
            $conn = connection::getPDOConnection();
        }
        printf("taskid %s ending." . PHP_EOL, $this->getID());

        sleep(1);

        printf("good bye world I was task upgrade to version 3.1" . PHP_EOL);

        flush();

        return $ret;
    }

    private function how_many_left()
    {
        $todo = 0;
        $appbox = appbox::get_instance(\bootstrap::getCore());

        foreach ($appbox->get_databoxes() as $databox) {
            try {
                $connbas = $databox->get_connection();

                $sql = 'SELECT count(r.record_id) as total FROM record r, subdef s'
                    . ' WHERE ISNULL(uuid)'
                    . ' AND s.record_id = r.record_id AND s.name="document"';

                $stmt = $connbas->prepare($sql);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();

                if ($row) {
                    $todo += (int) $row['total'];
                }
            } catch (Exception $e) {

            }
        }

        return $todo;
    }
}

?>
