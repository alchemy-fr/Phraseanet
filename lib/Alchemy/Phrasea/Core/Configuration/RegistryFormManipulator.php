<?php

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Form\Configuration\MainConfigurationFormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Translation\TranslatorInterface;

class RegistryFormManipulator
{
    /**
     * @var FormFactoryInterface
     */
    private $factory;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param FormFactoryInterface $factory
     * @param TranslatorInterface $translator
     * @param array $languages
     */
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
        $data = array_replace_recursive($this->getDefaultData($currentConf), $currentConf);
        $form->setData($data);

        return $form;
    }

    /**
     * Gets the registry data given a submitted form.
     * Default configuration is returned if no form provided.
     *
     * @param FormInterface $form
     *
     * @param PropertyAccess $conf
     * @return array
     */
    public function getRegistryData(FormInterface $form = null, PropertyAccess $conf = null)
    {
        $data = [];

        if (null !== $form) {
            if (!$form->isSubmitted()) {
                throw new RuntimeException('Form must have been submitted');
            }
            $newData = $form->getData();
            $data = $this->filterNullValues($newData);
        }

        $currentConf = $conf ? ($conf->get('registry') ?: []) : [];

        return array_replace_recursive($this->getDefaultData($currentConf), $data);
    }

    private function filterNullValues(array &$array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $this->filterNullValues($value);
            }
            else if ($key !== 'geonames-server' && $value === null) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    private function getDefaultData(array $config)
    {
        return [
            'general' => [
                'title' => 'Phraseanet',
                'keywords' => null,
                'description' => null,
                'analytics' => null,
                'matomo-analytics-url' => null,
                'matomo-analytics-id' => null,
                'allow-indexation' => true,
                'home-presentation-mode' => 'GALLERIA',
                'default-subdef-url-ttl' => 7200,
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
                'validation-reminder-time-left-percent' => 20,
                'validation-expiration-days' => 10,
                'auth-required-for-export' => true,
                'tou-validation-required-for-export' => false,
                'export-title-choice' => false,
                'default-export-title' => 'title',
                'social-tools' => 'none',
                'enable-push-authentication' => false,
                'force-push-authentication' => false,
                'enable-feed-notification' => true,
                'download-link-validity' => 24,
                'export-stamp-choice' => false,
            ],
            'ftp'          => [
                'ftp-enabled' => false,
                'ftp-user-access' => false,
            ],
            'registration' => [
                'auto-select-collections' => true,
                'auto-register-enabled' => false,
            ],
            'maintenance'  => [
                'message' => 'The application is down for maintenance',
                'enabled' => false,
            ],
            'api-clients'  => [
                'api-enabled' => true,
                'navigator-enabled' => true,
                'office-enabled' => true,
                'adobe_cc-enabled' => true,
            ],
            'webservices'  => [
                'google-charts-enabled' => true,
                'geonames-server' => 'https://geonames.alchemyasp.com/',
                'captchas-enabled' => false,
                'recaptcha-public-key' => '',
                'recaptcha-private-key' => '',
                'trials-before-display' => 5,
            ],
            'executables'  => [
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
                'default-query' => '',
                'default-query-type' => 0,
            ],
            'email'        => [
                'emitter-email' => 'phraseanet@example.com',
                'prefix' => null,
                'smtp-enabled' => false,
                'smtp-auth-enabled' => false,
                'smtp-host' => null,
                'smtp-port' => null,
                'smtp-secure-mode' => 'tls',
                'smtp-user' => null,
                'smtp-password' => isset($config['email']['smtp-password']) ? $config['email']['smtp-password'] : null,
            ],
            'custom-links' => [
                [
                    'linkName'      => 'Phraseanet store',
                    'linkLanguage'  => 'all',
                    'linkUrl'       => 'http://store.alchemy.fr',
                    'linkLocation'  => 'help-menu',
                    'linkOrder'     =>  1,
                    'linkBold'      =>  false,
                    'linkColor'     =>  ''
                ]
            ]
        ];
    }
}
