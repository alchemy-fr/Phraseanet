<?php

use Alchemy\Phrasea\Application;

class patch_416RC4 implements patchInterface
{
    /** @var string */
    private $release = '4.1.6-rc4';

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

    private function patch_databox(databox $databox, Application $app)
    {
        foreach ($databox->get_meta_structure() as $databox_field) {
            if ($databox_field->get_gui_visible()) {
                // all field with gui_visible is printable before PHRAS-3697
                $databox_field->set_printable(true);
            } else {
                $databox_field->set_printable(false);
            }

            $databox_field->save();
        }
    }

    private function patch_appbox(base $databox, Application $app)
    {
    }
}
