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
use Doctrine\ORM\Query\ResultSetMapping;

class patch_383alpha4a implements patchInterface
{
    /** @var string */
    private $release = '3.8.3-alpha.4';

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
        $em = $app['EM'];
        $mappingFieldType = $mappingFieldResult = new ResultSetMapping();

        $rs = $em->createNativeQuery(
            "SHOW FIELDS FROM usr WHERE Field = 'usr_login';",
            $mappingFieldType->addScalarResult('Type', 'Type')
        )->getSingleResult();

        if (0 !== strpos(strtolower($rs['Type']), 'varbinary')) {
            return;
        }

        // As 'usr_login' field type is varbinary it can contain any charset (utf8 or latin1).
        // Compare usr_login to usr_login converted to utf8>utf32>utf8
        // will detect broken char for latin1 encoded string.
        // Then detected 'usr_login' fields  must be converted from latin1 to utf8.
        $rs = $em->createNativeQuery(
            'SELECT t.usr_id, t.login_utf8 FROM (
                SELECT usr_id,
                usr_login AS login_unknown_charset,
                CONVERT(CAST(usr_login AS CHAR CHARACTER SET latin1) USING utf8) AS login_utf8,
                CONVERT(CONVERT(CAST(usr_login AS CHAR CHARACTER SET utf8) USING utf32) USING utf8) AS login_utf8_utf32_utf8
                FROM usr
            ) AS t
            WHERE t.login_utf8_utf32_utf8 != t.login_unknown_charset',
            $mappingFieldResult->addScalarResult('usr_id', 'usr_id')->addScalarResult('login_utf8', 'login_utf8')
        )->getResult();

        foreach ($rs as $row) {
            $em->getConnection()->executeQuery(sprintf('UPDATE usr SET usr_login="%s" WHERE usr_id=%d', $row['login_utf8'], $row['usr_id']));
        }

        foreach (array(
                     // drop index
                     "ALTER TABLE usr DROP INDEX usr_login;",
                     // change field type
                     "ALTER TABLE usr MODIFY usr_login VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_bin;",
                     // recreate index
                     "CREATE UNIQUE INDEX usr_login ON usr (usr_login);"
                 ) as $sql) {
            $em->getConnection()->executeQuery($sql);
        }

        return true;
    }
}
