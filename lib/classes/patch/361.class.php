<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_361 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.6.1';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base &$appbox)
    {
        $Core = \bootstrap::getCore();

        $em = $Core->getEntityManager();

        $conn = $appbox->get_connection();

        $sql = 'SELECT sbas_id, record_id, id FROM BasketElements';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($result as $row) {
            $sbas_id = (int) $row['sbas_id'];

            try {
                $connbas = connection::getPDOConnection($sbas_id);
            } catch (\Exception $e) {
                $conn->exec('DELETE FROM ValidationDatas WHERE basket_element_id = ' . $row['id']);
                $conn->exec('DELETE FROM BasketElements WHERE id = ' . $row['id']);
                continue;
            }

            $sql = 'SELECT record_id FROM record WHERE record_id = :record_id';
            $stmt = $connbas->prepare($sql);
            $stmt->execute(array(':record_id' => $row['record_id']));
            $rowCount = $stmt->rowCount();
            $stmt->closeCursor();

            if ($rowCount == 0) {
                $conn->exec('DELETE FROM ValidationDatas WHERE basket_element_id = ' . $row['id']);
                $conn->exec('DELETE FROM BasketElements WHERE id = ' . $row['id']);
            }
        }

        $dql = "SELECT b FROM Entities\Basket b WHERE b.description != ''";

        $n = 0;
        $perPage = 100;

        $query = $em->createQuery($dql)
            ->setFirstResult($n)
            ->setMaxResults($perPage);

        $paginator = new Paginator($query, true);

        $count = count($paginator);

        while ($n < $count) {
            $query = $em->createQuery($dql)
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
            $em->flush();
        }

        $em->flush();

        return true;
    }
}

