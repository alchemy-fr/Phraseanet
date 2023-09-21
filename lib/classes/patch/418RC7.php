<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Databox\SubdefGroup;

class patch_418RC7 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc7';

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

    private $thumbSubstitution = null;

    private function patch_databox(databox $databox, Application $app)
    {
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];

        if($this->thumbSubstitution === null) {
            // first db
            $this->thumbSubstitution = $conf->get(['registry', 'modules', 'thumb-substitution']);
            $conf->remove(['registry', 'modules', 'thumb-substitution']);
        }

        if ($this->thumbSubstitution) {
            $subdefStructure = $databox->get_subdef_structure();
            $dom_struct = $databox->get_dom_structure();
            $dom_xp = $databox->get_xpath_structure();

            /** @var SubdefGroup $sdGroup */
            foreach ($subdefStructure as $sdGroup) {
                $group = $sdGroup->getName();

                $nodes = $dom_xp->query('//record/subdefs/subdefgroup[@name="' . $group . '"]/subdef[@name="thumbnail"]');
                if ($nodes->length > 0) {
                    $dom_sd = $nodes->item(0);

                    while (true) {
                        $nodes = $dom_xp->query('substituable', $dom_sd);
                        if ($nodes->length === 0) {
                            break;
                        }
                        $dom_sd->removeChild($nodes->item(0));
                    }
                    $dom_sd->appendChild(
                        $dom_struct->createElement('substituable')
                    )->appendChild(
                        $dom_struct->createTextNode('yes')
                    );
                }
            }

            $databox->saveStructure($dom_struct);
        }
    }

    private function patch_appbox(base $appbox, Application $app)
    {
    }
}
