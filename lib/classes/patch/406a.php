<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;


class patch_406a implements patchInterface
{
    /** @var string */
    private $release = '4.0.6a';

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
        $sql = "DROP TABLE IF EXISTS `log_colls`";
        $databox->get_connection()->prepare($sql)->execute();

        /*
         * no need to do those ops, it's done by system:upgrade after fixing the xml scheme
         *
        $sql = "ALTER TABLE `log_docs`\n"
            . "CHANGE `action` `action` ENUM(\n"
            . "  'push',\n"
            . "  'add',\n"
            . "  'validate',\n"
            . "  'edit',\n"
            . "  'collection',\n"
            . "  'status',\n"
            . "  'print',\n"
            . "  'substit',\n"
            . "  'publish',\n"
            . "  'download',\n"
            . "  'mail',\n"
            . "  'ftp',\n"
            . "  'delete',\n"
            . "  'collection_from',\n"
            . "  ''\n"
            . ")\n"
            . "CHARACTER SET ascii BINARY  NOT NULL  DEFAULT ''";
        try {
            $databox->get_connection()->prepare($sql)->execute();
        }
        catch(\Exception $e) {
            // no-op
        }

        $sql = "ALTER TABLE `log_docs` ADD `coll_id` INT(11) UNSIGNED NULL DEFAULT NULL,\n"
            . "  ADD INDEX(coll_id)";
        try {
            $databox->get_connection()->prepare($sql)->execute();
        }
        catch(\Exception $e) {
            // no-op (the field exists ?)
        }

        $sql = "ALTER TABLE `log_view` ADD `coll_id` INT(11) UNSIGNED NULL DEFAULT NULL,\n"
            . "  ADD INDEX(coll_id)";
        try {
            $databox->get_connection()->prepare($sql)->execute();
        }
        catch(\Exception $e) {
            // no-op (the field exists ?)
        }
        */

        return true;
    }
}
