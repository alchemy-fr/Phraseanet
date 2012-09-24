<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service\TemplateEngine;

use Alchemy\Phrasea\Core\Service\ServiceAbstract;

class Twig extends ServiceAbstract
{
    /**
     *
     * @var \Twig_Environment
     */
    protected $twig;
    protected $templatesPath = array();

    protected function init()
    {
        $this->templatesPath = $this->resolvePaths();

        try {
            if ( ! $this->options['debug']) {
                $this->options['cache'] = realpath(__DIR__ . '/../../../../../../tmp/cache_twig/');
            }

            $loader = new \Twig_Loader_Filesystem($this->templatesPath);
            $this->twig = new \Twig_Environment($loader, $this->options);
            $this->loadGlobals();
            $this->loadExtensions();
            $this->loadTests();
            $this->loadFilters();
        } catch (\Exception $e) {
            throw new \Exception(sprintf(
                    "Unable to create '%s' service for the following reason %s"
                    , __CLASS__
                    , $e->getMessage()
                )
            );
        }
    }

    /**
     * Load phraseanet global variable
     * it' s like any other template variable,
     * except that it’s available in all templates and macros
     * @return void
     */
    private function loadGlobals()
    {
        $appbox = \appbox::get_instance($this->core);
        $session = $appbox->get_session();
        $browser = \Browser::getInstance();
        $registry = $appbox->get_registry();
        $request = new \http_request();

        $user = false;
        if ($session->is_authenticated()) {
            $user = \User_Adapter::getInstance($session->get_usr_id(), $appbox);
        }

        $core = \bootstrap::execute();
        $eventsmanager = $core['events-manager'];

        $this->twig->addGlobal('session', $session);
        $this->twig->addGlobal('version_number', $core->getVersion()->getNumber());
        $this->twig->addGlobal('version_name', $core->getVersion()->getName());
        $this->twig->addGlobal('core', $core);
        $this->twig->addGlobal('browser', $browser);
        $this->twig->addGlobal('request', $request);
        $this->twig->addGlobal('events', $eventsmanager);
        $this->twig->addGlobal('display_chrome_frame', $registry->is_set('GV_display_gcf') ? $registry->get('GV_display_gcf') : true);
        $this->twig->addGlobal('user', $user);
        $this->twig->addGlobal('current_date', new \DateTime());
        $this->twig->addGlobal('home_title', $registry->get('GV_homeTitle'));
        $this->twig->addGlobal('meta_description', $registry->get('GV_metaDescription'));
        $this->twig->addGlobal('meta_keywords', $registry->get('GV_metaKeywords'));
        $this->twig->addGlobal('maintenance', $registry->get('GV_maintenance'));
        $this->twig->addGlobal('registry', $registry);
    }

    /**
     * Load twig extensions
     * @return void
     */
    private function loadExtensions()
    {
        $this->twig->addExtension(new \Twig_Extension_Core());
        $this->twig->addExtension(new \Twig_Extension_Optimizer());
        $this->twig->addExtension(new \Twig_Extension_Escaper());
        $this->twig->addExtension(new \Twig_Extensions_Extension_Debug());
        // add filter trans
        $this->twig->addExtension(new \Twig_Extensions_Extension_I18n());
        // add filter localizeddate
        $this->twig->addExtension(new \Twig_Extensions_Extension_Intl());
        // add filters truncate, wordwrap, nl2br
        $this->twig->addExtension(new \Twig_Extensions_Extension_Text());
        $this->twig->addExtension(new \Alchemy\Phrasea\Twig\JSUniqueID());
    }

    private function loadTests()
    {
        $this->twig->addTest('null', new \Twig_Test_Function('is_null'));
    }

    /**
     * Load twig filters
     * return void
     */
    private function loadFilters()
    {
        $this->twig->addFilter('serialize', new \Twig_Filter_Function('serialize'));
        $this->twig->addFilter('stristr', new \Twig_Filter_Function('stristr'));
        $this->twig->addFilter('implode', new \Twig_Filter_Function('implode'));
        $this->twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
        $this->twig->addFilter('stripdoublequotes', new \Twig_Filter_Function('stripdoublequotes'));
        $this->twig->addFilter('geoname_display', new \Twig_Filter_Function('geonames::name_from_id'));
        $this->twig->addFilter('get_collection_logo', new \Twig_Filter_Function('collection::getLogo'));
        $this->twig->addFilter('floor', new \Twig_Filter_Function('floor'));
        $this->twig->addFilter('bas_names', new \Twig_Filter_Function('phrasea::bas_names'));
        $this->twig->addFilter('sbas_names', new \Twig_Filter_Function('phrasea::sbas_names'));
        $this->twig->addFilter('urlencode', new \Twig_Filter_Function('urlencode'));
        $this->twig->addFilter('sbasFromBas', new \Twig_Filter_Function('phrasea::sbasFromBas'));
        $this->twig->addFilter('key_exists', new \Twig_Filter_Function('array_key_exists'));
        $this->twig->addFilter('array_keys', new \Twig_Filter_Function('array_keys'));
        $this->twig->addFilter('round', new \Twig_Filter_Function('round'));
        $this->twig->addFilter('formatDate', new \Twig_Filter_Function('phraseadate::getDate'));
        $this->twig->addFilter('prettyDate', new \Twig_Filter_Function('phraseadate::getPrettyString'));
        $this->twig->addFilter('formatOctets', new \Twig_Filter_Function('p4string::format_octets'));
        $this->twig->addFilter('geoname_name_from_id', new \Twig_Filter_Function('geonames::name_from_id'));
        $this->twig->addFilter('base_from_coll', new \Twig_Filter_Function('phrasea::baseFromColl'));
    }

    private function getDefaultTemplatePath()
    {
        return array(
            'mobile' => array(
                __DIR__ . '/../../../../../../config/templates/mobile',
                __DIR__ . '/../../../../../../templates/mobile'
            ),
            'web' => array(
                __DIR__ . '/../../../../../../config/templates/web',
                __DIR__ . '/../../../../../../templates/web'
            )
        );
    }

    /**
     * Set default templates Path
     * According to the client device
     * @return void
     */
    private function resolvePaths()
    {
        $browser = \Browser::getInstance();

        $templatePath = $this->getDefaultTemplatePath();

        if ($browser->isTablet() || $browser->isMobile()) {
            $paths = $templatePath['mobile'];
        } else {
            $paths = $templatePath['web'];
        }

        return $paths;
    }

    public function getDriver()
    {
        return $this->twig;
    }

    public function getType()
    {
        return 'twig';
    }

    public function getMandatoryOptions()
    {
        return array('debug', 'charset', 'strict_variables', 'autoescape', 'optimizer');
    }
}
