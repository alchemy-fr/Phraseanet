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
use Doctrine\ORM\Query\ResultSetMapping;

class patch_383alpha1a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.3-alpha.1';

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
    public function apply(base $appbox, Application $app)
    {
        if (!$this->hasSessionTable($app)) {
            return true;
        }

        // Remove deleted users sessions
        $sql = 'SELECT s.id FROM `Sessions` s INNER JOIN Users u ON (u.id = s.user_id) WHERE u.deleted = 1';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rows as $row) {
            if (null !== $session = $app['repo.sessions']->find($row['id'])) {
                $app['orm.em']->remove($session);
            }
        }

        // Remove API sessions
        $query = $app['orm.em']->createQuery('SELECT s FROM Phraseanet:Session s WHERE s.user_agent LIKE :guzzle');
        $query->setParameter(':guzzle', 'Guzzle%');

        foreach ($query->getResult() as $session) {
            $app['orm.em']->remove($session);
        }

        $app['orm.em']->flush();

        return true;
    }

    private function hasSessionTable(Application $app)
    {
        $rsm = (new ResultSetMapping())->addScalarResult('Name', 'Name');
        $ret = false;

        foreach ($app['orm.em']->createNativeQuery('SHOW TABLE STATUS', $rsm)->getResult() as $row) {
            if ('Session' === $row['Name']) {
                $ret = true;
                break;
            }
        }

        return $ret;
    }
}
