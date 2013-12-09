<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_390alpha9a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.9';

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
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $this->updateRegistry($app);
        $this->updateUsers($appbox);
        $this->updateDoctrineUsers($app);
        $this->updateDataboxPrefs($appbox);
    }

    private function updateRegistry(Application $app)
    {
        $locale = $app['phraseanet.registry']->get('GV_default_lng', 'fr_FR');
        $app['phraseanet.registry']->set('GV_default_lng', $this->extractLocale($locale), \registry::TYPE_STRING);
    }

    private function updateUsers(\appbox $appbox)
    {
        $sql = 'SELECT usr_id, locale FROM usr WHERE locale IS NOT NULL';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'UPDATE usr SET locale = :locale WHERE usr_id = :usr_id';
        $stmt = $appbox->get_connection()->prepare($sql);

        foreach ($rows as $row) {
            $stmt->execute([
                ':locale' => $this->extractLocale($row['locale']),
                ':usr_id' => $row['usr_id'],
            ]);
        }

        $stmt->closeCursor();
    }

    private function updateDoctrineUsers(Application $app)
    {
        $dql = 'SELECT u FROM Alchemy\Phrasea\Model\Entities\User u WHERE u.locale IS NOT NULL';
        $users = $app['EM']->createQuery($dql)->getResult();

        foreach ($users as $user) {
            $user->setLocale($this->extractLocale($user->getLocale()));
            $app['EM']->persist($user);
        }

        $app['EM']->flush();
    }

    private function updateDataboxPrefs(\appbox $appbox)
    {
        foreach ($appbox->get_databoxes() as $databox) {
            $sql = 'SELECT id, locale FROM pref WHERE prop = "ToU"';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $sql = 'UPDATE pref SET locale = :locale WHERE id = :id';
            $stmt = $databox->get_connection()->prepare($sql);

            foreach ($rows as $row) {
                $stmt->execute([
                    ':locale' => $this->extractLocale($row['locale']),
                    ':id'     => $row['id'],
                ]);
            }

            $stmt->closeCursor();
        }
    }

    private function extractLocale($locale)
    {
        $data = explode('_', $locale);

        return $data[0];
    }
}
