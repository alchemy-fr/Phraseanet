<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_390alpha9a extends patchAbstract
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
    public function apply(base $appbox, Application $app)
    {
        $this->updateRegistry($app);
        $this->updateDoctrineUsers($app);
        $this->updateDataboxPrefs($appbox);
    }

    private function updateRegistry(Application $app)
    {
        $sql = 'SELECT `value` FROM registry WHERE `key` = :key';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':key' => 'GV_default_lng']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $locale = null !== $row ? $row['value'] : 'fr';

        $sql = 'UPDATE registry SET `value` = :value WHERE `key` = :key';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute([':key' => 'GV_default_lng', ':value' => $this->extractLocale($locale)]);
        $stmt->closeCursor();
    }

    private function updateDoctrineUsers(Application $app)
    {
        $dql = 'SELECT u FROM Phraseanet:User u WHERE u.locale IS NOT NULL';
        $users = $app['orm.em']->createQuery($dql)->getResult();

        foreach ($users as $user) {
            $user->setLocale($this->extractLocale($user->getLocale()));
            $app['orm.em']->persist($user);
        }

        $app['orm.em']->flush();
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
