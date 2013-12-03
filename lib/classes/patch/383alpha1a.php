<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_383alpha1a implements patchInterface
{
    /** @var string */
    private $release = '3.8.3-alpha.1';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

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
        return [];
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
        // Remove deleted users sessions
        $sql = 'SELECT s.id FROM `Sessions` s, usr u WHERE u.usr_login LIKE "(#deleted%" AND u.usr_id = s.usr_id';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rows as $row) {
            if (null !== $session = $app['EM']->find('Alchemy\Phrasea\Model\Entities\Session', $row['id'])) {
                $app['EM']->remove($session);
            }
        }

        // Remove API sessions
        $query = $app['EM']->createQuery('SELECT s FROM Alchemy\Phrasea\Model\Entities\Session s WHERE s.user_agent LIKE :guzzle');
        $query->setParameter(':guzzle', 'Guzzle%');

        foreach ($query->getResult() as $session) {
            $app['EM']->remove($session);
        }

        $app['EM']->flush();

        return true;
    }
}
