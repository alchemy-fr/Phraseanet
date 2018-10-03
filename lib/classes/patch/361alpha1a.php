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
use Doctrine\ORM\Tools\Pagination\Paginator;

class patch_361alpha1a extends patchAbstract
{
    /** @var string */
    private $release = '3.6.1-alpha.1';

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
    public function require_all_upgrades()
    {
        return true;
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
    public function getDoctrineMigrations()
    {
        return ['20131118000002'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $conn = $appbox->get_connection();

        $sql = 'SELECT sbas_id, record_id, id FROM BasketElements';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($result as $row) {
            $sbas_id = (int) $row['sbas_id'];

            try {
                $connbas = $app->findDataboxById($sbas_id)->get_connection();
                $connbas->connect();
            } catch (\Exception $e) {
                $conn->exec('DELETE FROM ValidationDatas WHERE basket_element_id = ' . $row['id']);
                $conn->exec('DELETE FROM BasketElements WHERE id = ' . $row['id']);
                continue;
            }

            $sql = 'SELECT record_id FROM record WHERE record_id = :record_id';
            $stmt = $connbas->prepare($sql);
            $stmt->execute([':record_id' => $row['record_id']]);
            $rowCount = $stmt->rowCount();
            $stmt->closeCursor();

            if ($rowCount == 0) {
                $conn->exec('DELETE FROM ValidationDatas WHERE basket_element_id = ' . $row['id']);
                $conn->exec('DELETE FROM BasketElements WHERE id = ' . $row['id']);
            }
        }

        $dql = "SELECT b FROM Phraseanet:Basket b WHERE b.description != ''";

        $n = 0;
        $perPage = 100;

        $query = $app['orm.em']->createQuery($dql)
            ->setFirstResult($n)
            ->setMaxResults($perPage);

        $paginator = new Paginator($query, true);

        $count = count($paginator);

        while ($n < $count) {
            $query = $app['orm.em']->createQuery($dql)
                ->setFirstResult($n)
                ->setMaxResults($perPage);

            $paginator = new Paginator($query, true);

            foreach ($paginator as $basket) {
                $htmlDesc = $basket->getDescription();

                $description = trim(strip_tags(str_replace("<br />", "\n", $htmlDesc)));

                if ($htmlDesc == $description) {
                    continue;
                }

                $basket->setDescription($description);
            }

            $n += $perPage;
            $app['orm.em']->flush();
        }

        $app['orm.em']->flush();

        return true;
    }
}
