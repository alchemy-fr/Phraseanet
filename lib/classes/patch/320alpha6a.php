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

class patch_320alpha6a extends patchAbstract
{
    /** @var string */
    private $release = '3.2.0-alpha.6';

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
        $sql = 'UPDATE record r, subdef s
                SET r.mime = s.mime
                WHERE r.record_id = s.record_id
                  AND s.name="document"';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'UPDATE subdef s, record r
                SET s.updated_on = r.moddate, s.created_on = r.credate
                WHERE s.record_id = r.record_id';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'UPDATE subdef SET `name` = LOWER( `name` )';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $dom = $databox->get_dom_structure();
        $xpath = $databox->get_xpath_structure();

        $nodes = $xpath->query('//record/subdefs/subdefgroup/subdef');

        foreach ($nodes as $node) {
            $name = mb_strtolower(trim($node->getAttribute('name')));
            if ($name === '')
                continue;
            $node->setAttribute('name', $name);
        }

        $databox->saveStructure($dom);

        return true;
    }
}
