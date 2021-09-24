<?php

use Alchemy\Phrasea\Application;

class patch_414 implements patchInterface
{
    /** @var string */
    private $release = '4.1.4';
    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $base, Application $app)
    {
        $cnx = $base->get_connection();
        $sql = "CREATE TEMPORARY TABLE `_logs` ( `usr_id` INT(11) NOT NULL , `connection` DATETIME NOT NULL , INDEX `usr_id` (`usr_id`)) ENGINE = InnoDB";
        $cnx->exec($sql);

        $sql = "INSERT INTO `_logs` (`usr_id`, `connection`) VALUES (:usr_id, :connection)";
        $stmti = $cnx->prepare($sql);

        foreach($app->getApplicationBox()->get_databoxes() as $databox) {
            $sql = "SELECT `usrid` AS `usr_id`, MAX(`date`) AS `connection` FROM `log` WHERE NOT ISNULL(`usrid`) AND NOT ISNULL(`date`) GROUP BY `usrid`";
            try {
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute();
                while($row = ($stmt->fetch())) {
                    $stmti->execute($row);
                }
                $stmt->closeCursor();
            }
            catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }

        $sql = "UPDATE `Users` SET `last_connection` = (SELECT MAX(`connection`) FROM `_logs` WHERE _logs.usr_id = Users.id)";
        try {
            $stmt = $cnx->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }
        catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        $stmti->closeCursor();

        $sql = "DROP TABLE `_logs`";
        $cnx->exec($sql);

        return true;
    }
}
