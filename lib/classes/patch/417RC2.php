<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_417RC2 implements patchInterface
{
    /** @var string */
    private $release = '4.1.7-rc2';

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
    public function apply(base $base, Application $app)
    {
        if ($base->get_base_type() === base::DATA_BOX) {
            $this->patch_databox($base, $app);
        }
        elseif ($base->get_base_type() === base::APPLICATION_BOX) {
            $this->patch_appbox($base, $app);
        }

        return true;
    }

    private function patch_databox(databox $databox, Application $app)
    {
    }

    private function patch_appbox(base $appbox, Application $app)
    {
        $cnx = $appbox->get_connection();

        /** @var PropertyAccess $conf */
        $conf = $app['conf'];

        // add new conf if not exist
        // default value to 15 days ??
        // or null for download never expired
        if (!$conf->has(['order-manager', 'download-hd', 'expiration-days'])) {
            $conf->set(['order-manager', 'download-hd', 'expiration-days'], 15);
        }

        if (!$conf->has(['order-manager', 'download-hd', 'overridable-setting'])) {
            $conf->set(['order-manager', 'download-hd', 'overridable-setting'], false);
        }

        // needed to expire existing order download ???
        $expirationDays = $app['conf']->get(['order-manager', 'download-hd', 'expiration-days'], 15);
        $expireOn = (new \DateTime('+ '. $expirationDays .' day'))->format('Y-m-d h:m:s');

        $sql = 'UPDATE `records_rights` SET `expire_on` = :expire_on WHERE `case` = "order" AND `expire_on` IS NULL';
        $stmt = $cnx->prepare($sql);
        $stmt->execute([':expire_on' => $expireOn]);
        $stmt->closeCursor();

    }
}
