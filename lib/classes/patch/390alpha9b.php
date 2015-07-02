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
use Alchemy\Phrasea\Model\Entities\Task;

class patch_390alpha9b extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.9b';

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
    public function require_all_upgrades()
    {
        return true;
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
    public function apply(base $appbox, Application $app)
    {
        $this->upgradeConf($app);
        $this->upgradeRegistry($app);
    }

    private function upgradeRegistry(Application $app)
    {
        $sql = 'SELECT `key`, `value`, `type` FROM registry';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $oldRegistry = [];

        foreach ($rows as $row) {
            switch ($row['type']) {
                case 'boolean':
                    $value = (Boolean) $row['value'];
                    break;
                case 'integer':
                    $value = (int) $row['value'];
                    break;
                default:
                case 'enum':
                case 'string':
                case 'text':
                case 'timezone':
                    $value = $row['value'];
                    break;
                case 'binary':
                case 'enum_multi':
                    continue;
                    break;
            }

            $oldRegistry[$row['key']] = $value;
        }

        $config = $app['configuration.store']->getConfig();
        $config['languages']['default'] = isset($oldRegistry['GV_default_lng']) ? $oldRegistry['GV_default_lng'] : 'fr';
        $config['registry'] = $app['registry.manipulator']->getRegistryData();
        $app['configuration.store']->setConfig($config);

        $mapping = [
            'GV_homeTitle' => ['registry', 'general', 'title'],
            'GV_metaKeywords' => ['registry', 'general', 'keywords'],
            'GV_metaDescription' => ['registry', 'general', 'description'],
            'GV_googleAnalytics' => ['registry', 'general', 'analytics'],
            'GV_allow_search_engine' => ['registry', 'general', 'allow-indexation'],
            'GV_home_publi' => ['registry', 'general', 'home-presentation-mode'],
            'GV_thesaurus' => ['registry', 'modules', 'thesaurus'],
            'GV_multiAndReport' => ['registry', 'modules', 'stories'],
            'GV_seeOngChgDoc' => ['registry', 'modules', 'doc-substitution'],
            'GV_seeNewThumb' => ['registry', 'modules', 'thumb-substitution'],
            'GV_anonymousReport' => ['registry', 'modules', 'anonymous-report'],
            'GV_download_max' => ['registry', 'actions', 'download-max-size'],
            'GV_validation_reminder' => ['registry', 'actions', 'validation-reminder-days'],
            'GV_val_expiration' => ['registry', 'actions', 'validation-expiration-days'],
            'GV_needAuth2DL' => ['registry', 'actions', 'auth-required-for-export'],
            'GV_requireTOUValidationForExport' => ['registry', 'actions', 'tou-validation-required-for-export'],
            'GV_choose_export_title' => ['registry', 'actions', 'export-title-choice'],
            'GV_default_export_title' => ['registry', 'actions', 'default-export-title'],
            'GV_social_tools' => ['registry', 'actions', 'social-tools'],
            'GV_activeFTP' => ['registry', 'ftp', 'ftp-enabled'],
            'GV_ftp_for_user' => ['registry', 'ftp', 'ftp-user-access'],
            'GV_autoselectDB' => ['registry', 'registration', 'auto-select-collections'],
            'GV_autoregister' => ['registry', 'registration', 'auto-register-enabled'],
            'GV_ong_search' => ['registry', 'classic', 'search-tab'],
            'GV_ong_advsearch' => ['registry', 'classic', 'adv-search-tab'],
            'GV_ong_topics' => ['registry', 'classic', 'topics-tab'],
            'GV_ong_actif' => ['registry', 'classic', 'active-tab'],
            'GV_client_render_topics' => ['registry', 'classic', 'render-topics'],
            'GV_rollover_reg_preview' => ['registry', 'classic', 'stories-preview'],
            'GV_rollover_chu' => ['registry', 'classic', 'basket-rollover'],
            'GV_client_coll_ckbox' => ['registry', 'classic', 'collection-presentation'],
            'GV_viewSizeBaket' => ['registry', 'classic', 'basket-size-display'],
            'GV_clientAutoShowProposals' => ['registry', 'classic', 'auto-show-proposals'],
            'GV_view_bas_and_coll' => ['registry', 'classic', 'collection-display'],
            'GV_message' => ['registry', 'maintenance', 'message'],
            'GV_message_on' => ['registry', 'maintenance', 'enabled'],
            'GV_client_navigator' => ['registry', 'api-clients', 'navigator-enabled'],
            'GV_client_officeplugin' => ['registry', 'api-clients', 'office-enabled'],
            'GV_google_api' => ['registry', 'webservices', 'google-charts-enabled'],
            'GV_i18n_service' => ['registry', 'webservices', 'geonames-server'],
            'GV_captchas' => ['registry', 'webservices', 'captcha-enabled'],
            'GV_captcha_public_key' => ['registry', 'webservices', 'recaptcha-public-key'],
            'GV_captcha_private_key' => ['registry', 'webservices', 'recaptcha-private-key'],
            'GV_h264_streaming' => ['registry', 'executables', 'h264-streaming-enabled'],
            'GV_mod_auth_token_directory' => ['registry', 'executables', 'auth-token-directory'],
            'GV_mod_auth_token_directory_path' => ['registry', 'executables', 'auth-token-directory-path'],
            'GV_mod_auth_token_passphrase' => ['registry', 'executables', 'auth-token-passphrase'],
            'GV_PHP_INI' => ['registry', 'executables', 'php-conf-path'],
            'GV_imagine_driver' => ['registry', 'executables', 'imagine-driver'],
            'GV_ffmpeg_threads' => ['registry', 'executables', 'ffmpeg-threads'],
            'GV_pdfmaxpages' => ['registry', 'executables', 'pdf-max-pages'],
            'GV_min_letters_truncation' => ['registry', 'searchengine', 'min-letters-truncation'],
            'GV_defaultQuery' => ['registry', 'searchengine', 'default-query'],
            'GV_defaultQuery_type' => ['registry', 'searchengine', 'default-query-type'],
            'GV_adminMail' => ['registry', 'email', 'admin-email'],
            'GV_defaulmailsenderaddr' => ['registry', 'email', 'emitter-email'],
            'GV_email_prefix' => ['registry', 'email', 'prefix'],
            'GV_smtp' => ['registry', 'email', 'smtp-enabled'],
            'GV_smtp_auth' => ['registry', 'email', 'smtp-auth-enabled'],
            'GV_smtp_host' => ['registry', 'email', 'smtp-host'],
            'GV_smtp_port' => ['registry', 'email', 'smtp-port'],
            'GV_smtp_secure' => ['registry', 'email', 'smtp-secure-mode'],
            'GV_smtp_user' => ['registry', 'email', 'smtp-user'],
            'GV_smtp_password' => ['registry', 'email', 'smtp-password'],
            'GV_base_datapath_noweb' => ['main', 'storage', 'subdefs'],
            'GV_youtube_api' => ['main', 'bridge', 'youtube', 'enabled'],
            'GV_youtube_client_id' => ['main', 'bridge', 'youtube', 'client_id'],
            'GV_youtube_client_secret' => ['main', 'bridge', 'youtube', 'client_secret'],
            'GV_youtube_dev_key' => ['main', 'bridge', 'youtube', 'developer_key'],
            'GV_flickr_api' => ['main', 'bridge', 'flickr', 'enabled'],
            'GV_flickr_client_id' => ['main', 'bridge', 'flickr', 'client_id'],
            'GV_flickr_client_secret' => ['main', 'bridge', 'flickr', 'client_secret'],
            'GV_dailymotion_api' => ['main', 'bridge', 'dailymotion', 'enabled'],
            'GV_dailymotion_client_id' => ['main', 'bridge', 'dailymotion', 'client_id'],
            'GV_dailymotion_client_secret' => ['main', 'bridge', 'dailymotion', 'client_secret'],
        ];

        foreach ($mapping as $source => $target) {
            if (!isset($oldRegistry[$source])) {
                continue;
            }
            $app['conf']->set($target, $oldRegistry[$source]);
        }
    }

    private function upgradeConf(Application $app)
    {
        $config = $app['configuration.store']->getConfig();

        if (isset($config['main']['languages'])) {
            $config = array_merge(['languages' => ['available' => $config['main']['languages']]], $config);
            unset($config['main']['languages']);
        }

        if (isset($config['main']['servername'])) {
            $config = array_merge(['servername' => $config['main']['servername']], $config);
            unset($config['main']['servername']);
        }

        if (isset($config['task-manager'])) {
            $config['main']['task-manager'] = $config['task-manager'];
            unset($config['task-manager']);
        }

        if (isset($config['binaries'])) {
            $binaries = isset($config['main']['binaries']) ? $config['main']['binaries'] : [];
            $config['main']['binaries'] = array_merge($binaries, $config['binaries']);
            unset($config['binaries']);
        }

        $app['configuration.store']->setConfig($config);
    }
}
