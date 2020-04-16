<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;


class patch_4012 implements patchInterface
{
    /** @var string */
    private $release = '4.0.12';

    /** @var array */
    private $concern = [base::DATA_BOX];

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
    public function apply(base $databox, Application $app)
    {
        // script to fix the correct user right on a databox collections
        foreach ($app['repo.users']->findAll() as $user) {
            $acl = $app->getAclForUser($user);

            // check if user has access to databox
            if ($acl->has_access_to_sbas($databox->get_sbas_id())) {
                $acl->delete_injected_rights_sbas($databox);

                $sql = "INSERT INTO collusr
                      (site, usr_id, coll_id, mask_and, mask_xor, ord)
                      VALUES (:site_id, :usr_id, :coll_id, :mask_and, :mask_xor, :ord)";
                $stmt = $databox->get_connection()->prepare($sql);
                $iord = 0;

                //  fix collusr if user has right on collection
                foreach ($acl->get_granted_base([], [$databox->get_sbas_id()]) as $collection) {
                    try {
                        $stmt->execute([
                            ':site_id'  => $app['conf']->get(['main', 'key']),
                            ':usr_id'   => $user->getId(),
                            ':coll_id'  => $collection->get_coll_id(),
                            ':mask_and' => $acl->get_mask_and($collection->get_base_id()),
                            ':mask_xor' => $acl->get_mask_xor($collection->get_base_id()),
                            ':ord'      => $iord++
                        ]);
                    } catch (\Doctrine\DBAL\DBALException $e) {

                    }
                }

                $stmt->closeCursor();
            }

            unset($acl);
        }

        // script used for the slow query indexing fix
        $sql = "ALTER TABLE `record` ADD INDEX `moddate` (`moddate`);";
        try {
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }
        catch (\Exception $e) {
            // the index already exists ?
        }

        return true;
    }
}
