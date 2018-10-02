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
use Doctrine\ORM\NoResultException;

class patch_383alpha2a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.3-alpha.2';

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
    public function getDoctrineMigrations()
    {
        return ['20131118000002'];
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
        // Clean validation sessions where initiator_id does not exist anymore
        $sql = 'SELECT DISTINCT(v.id) AS validation_session_id FROM `ValidationSessions` v LEFT JOIN Users u ON (v.initiator_id = u.id) WHERE u.id IS NULL';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rows as $row) {
            try {
                $vsession = $app['orm.em']->createQuery('SELECT PARTIAL s.{id} FROM Phraseanet:ValidationSession s WHERE s.id = :id')
                      ->setParameters(['id' => $row['validation_session_id']])
                      ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                      ->getSingleResult();
                $app['orm.em']->remove($vsession);
            } catch (NoResultException $e) {

            }
        }

        $app['orm.em']->flush();

        return true;
    }
}
