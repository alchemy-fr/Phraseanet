<?php

use Alchemy\Phrasea\Application;

class patch_412 implements patchInterface
{
    /** @var string */
    private $release = '4.1.2';

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
     * Apply patch.
     *
     * @param base $base The Application Box or the Data Boxes where the patch is applied.
     * @param \Alchemy\Phrasea\Application $app
     *
     * @return boolean returns true if the patch succeed.
     */
    public function apply(base $appbox, Application $app)
    {
        // move api-require-ssl place in configuration.yml
        if ($app['conf']->has(['main', 'api_require_ssl'])) {
            $apiRequireSslValue = $app['conf']->get(['main', 'api_require_ssl']);
            $app['conf']->remove(['main', 'api_require_ssl']);
            $app['conf']->set(['registry', 'api-clients', 'api-require-ssl'], $apiRequireSslValue);
        }

        // change api_token_header place and name in configuration.yml
        if ($app['conf']->has(['main', 'api_token_header'])) {
            $apiTokenHeaderValue = $app['conf']->get(['main', 'api_token_header']);
            $app['conf']->remove(['main', 'api_token_header']);
            $app['conf']->set(['registry', 'api-clients', 'api-auth-token-header-only'], $apiTokenHeaderValue);
        }

        // add svg in extension-mapping
        if (!$app['conf']->has(['border-manager', 'extension-mapping', 'svg'])) {
            $app['conf']->set(['border-manager', 'extension-mapping', 'svg'], 'image/svg+xml');
        }
    }
}
