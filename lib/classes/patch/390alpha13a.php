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
use Alchemy\Phrasea\Model\Entities\Registration;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;

class patch_390alpha13a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.13';

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
        return ['20140226000001'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $em = $app['orm.em'];

        $sql = "SELECT date_modif, usr_id, base_id, en_cours, refuser
                FROM demand";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('base_id','base_id');
        $rsm->addScalarResult('en_cours','en_cours');
        $rsm->addScalarResult('refuser','refuser');
        $rsm->addScalarResult('usr_id', 'usr_id');
        $rsm->addScalarResult('date_modif', 'date_modif');

        $rs = $em->createNativeQuery($sql, $rsm)->getResult();
        $n = 0;

        foreach ($rs as $row) {
            try {
                $user = $em->createQuery('SELECT PARTIAL u.{id} FROM Phraseanet:User u WHERE u.id = :id')
                    ->setParameters(['id' => $row['usr_id']])
                    ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                $app['monolog']->addInfo(sprintf(
                    'Patch %s : Registration for user (%s) could not be turn into doctrine entity as user could not be found.',
                    $this->get_release(),
                    $row['usr_id']
                ));
                continue;
            }

            try {
                $collection = \collection::getByBaseId($app, $row['base_id']);
            } catch (\Exception $e) {
                $app['monolog']->addInfo(sprintf(
                    'Patch %s : Registration for user (%s) could not be turn into doctrine entity as base with id (%s) could not be found.',
                    $this->get_release(),
                    $row['usr_id'],
                    $row['base_id']
                ));
                continue;
            }

            $registration = new Registration();
            $registration->setCollection($collection);
            $registration->setUser($user);
            $registration->setPending($row['en_cours']);
            $registration->setCreated(new \DateTime($row['date_modif']));
            $registration->setRejected($row['refuser']);

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
            $n++;
        }

        $em->flush();
        $em->clear();
    }
}
