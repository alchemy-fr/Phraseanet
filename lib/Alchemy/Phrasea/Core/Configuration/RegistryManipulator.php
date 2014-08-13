<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Form\Configuration\MainConfigurationFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RegistryManipulator
{
    private $factory;
    private $languages;
    private $translator;

    public function __construct(FormFactoryInterface $factory, TranslatorInterface $translator, array $languages)
    {
        $this->factory = $factory;
        $this->languages = $languages;
        $this->translator = $translator;
    }

    /**
     * Creates a setup form. Set data if a configuration is given.
     *
     * @param PropertyAccess $conf
     *
     * @return FormInterface
     */
    public function createForm(PropertyAccess $conf = null)
    {
        $form = $this->factory->create(new MainConfigurationFormType($this->translator, $this->languages));
        $currentConf = $conf ? ($conf->get('registry') ?: []) : [];
        $data = array_replace_recursive($this->getDefaultData(), $currentConf);
        $form->setData($data);

        return $form;
    }

    /**
     * Gets the registry data given a submitted form.
     * Default configuration is returned if no form provided.
     *
     * @param FormInterface $form
     *
     * @return array
     *
     * @throws RuntimeException
     */
    public function getRegistryData(FormInterface $form = null)
    {
        $data = [];

        if (null !== $form) {
            if (!$form->isSubmitted()) {
                throw new RuntimeException('Form must have been submitted');
            }
            $newData = $form->getData();
            $data = $this->filterNullValues($newData);
        }

        return array_replace_recursive($this->getDefaultData(), $data);
    }

    private function filterNullValues(array &$array)
    {
        return array_filter($array, function (&$value) {
            if (is_array($value)) {
                $value = $this->filterNullValues($value);
            }

            return null !== $value;
        });
    }

    private function getDefaultData()
    {
        return [
            'general' => [
                'title' => 'Phraseanet',
                'keywords' => null,
                'description' => null,
                'analytics' => null,
                'allow-indexation' => true,
                'home-presentation-mode' => 'GALLERIA',
            ],
            'modules' => [
                'thesaurus' => true,
                'stories' => true,
                'doc-substitution' => true,
                'thumb-substitution' => true,
                'anonymous-report' => false,
            ],
            'actions' => [
                'download-max-size' => 120,
                'validation-reminder-days' => 2,
                'validation-expiration-days' => 10,
                'auth-required-for-export' => true,
                'tou-validation-required-for-export' => false,
                'export-title-choice' => false,
                'default-export-title' => 'title',
                'social-tools' => 'none',
                'enable-push-authentication' => false,
                'force-push-authentication' => false,
                'enable-feed-notification' => true
            ],
            'ftp' => [
                'ftp-enabled' => false,
                'ftp-user-access' => false,
            ],
            'registration' => [
                'auto-select-collections' => true,
                'auto-register-enabled' => false,
            ],
            'classic' => [
                'search-tab' => 1,
                'adv-search-tab' => 2,
                'topics-tab' => 0,
                'active-tab' => 1,
                'render-topics' => 'tree',
                'stories-preview' => true,
                'basket-rollover' => true,
                'collection-presentation' => 'checkbox',
                'basket-size-display' => true,
                'auto-show-proposals' => true,
                'collection-display' => true,
            ],
            'maintenance' => [
                'message' => 'The application is down for maintenance',
                'enabled' => false,
            ],
            'api-clients' => [
                'navigator-enabled' => true,
                'office-enabled' => true,
            ],
            'webservices' => [
                'google-charts-enabled' => true,
                'geonames-server' => 'http://geonames.alchemyasp.com/',
                'captchas-enabled' => false,
                'recaptcha-public-key' => '',
                'recaptcha-private-key' => '',
            ],
            'executables' => [
                'h264-streaming-enabled' => false,
                'auth-token-directory' => null,
                'auth-token-directory-path' => null,
                'auth-token-passphrase' => null,
                'php-conf-path' => null,
                'imagine-driver' => '',
                'ffmpeg-threads' => 2,
                'pdf-max-pages' => 5,
            ],
            'searchengine' => [
                'min-letters-truncation' => 1,
                'default-query' => 'all',
                'default-query-type' => 0,
            ],
            'email' => [
                'emitter-email' => 'phraseanet@example.com',
                'prefix' => null,
                'smtp-enabled' => false,
                'smtp-auth-enabled' => false,
                'smtp-host' => null,
                'smtp-port' => null,
                'smtp-secure-mode' => 'tls',
                'smtp-user' => null,
                'smtp-password' => null,
            ],
        ];
    }
}
