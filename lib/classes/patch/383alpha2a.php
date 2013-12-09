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

class patch_383alpha2a implements patchInterface
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
        // Clean validation sessions where initiator_id does not exist anymore
        $sql = 'SELECT DISTINCT(v.id) AS validation_session_id FROM `ValidationSessions` v LEFT JOIN usr u ON (v.initiator_id = u.usr_id) WHERE u.usr_id IS NULL';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rows as $row) {
            if (null !== $vsession = $app['EM']->find('Alchemy\Phrasea\Model\Entities\ValidationSession', $row['validation_session_id'])) {
                $app['EM']->remove($vsession);
            }
        }

        $app['EM']->flush();

        return true;
    }
}
