<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2019 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_410alpha25a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.25a';
    /** @var array */
    private $concern = [base::APPLICATION_BOX];
    /**
     * Returns the release version.
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
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
    public function require_all_upgrades()
    {
        return false;
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
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];
        $oldOptions = $conf->get(['main', 'search-engine', 'options'], []);
        $newOptions = [];
        $facets = [];
        // preserve former settings from conf (tech facets)
        foreach($oldOptions as $k=>$v) {
            if(substr($k, -16) === '_aggregate_limit') {
                // this option is moved under "facets"
                $k = substr($k, 0, strlen($k)-16);  // keep field name
                $facets['_'.$k] = ['limit' => $v];
            }
            else {
                $newOptions[$k] = $v;
            }
        }
        // add facets for fields
        foreach($app->getDataboxes() as $databox) {
            foreach($databox->get_meta_structure() as $field) {
                $facets[$field->get_name()] = ['limit' => $field->getFacetValuesLimit()];
            }
        }
        // facets in the end of settings
        $newOptions['facets'] = $facets;
        $conf->set(['main', 'search-engine', 'options'], $newOptions);

        return true;
    }
}
