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

class patch_401alpha1a implements patchInterface
{
    /** @var string */
    private $release = '4.0.1-alpha.1';
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
        return [
            '20171023124154'
        ];
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
        $sql = 'TRUNCATE TABLE memcached';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
        return true;
    }
}