<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Form\Constraint\NewLogin;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['registration.fields'] = $app->share(function (Application $app) {
            return isset($app['configuration']['registration-fields']) ? $app['configuration']['registration-fields'] : array();
        });

        $app['registration.enabled'] = $app->share(function (Application $app) {
            require_once __DIR__ . '/../../../../classes/deprecated/inscript.api.php';

            $bases = giveMeBases($app);

            if ($bases) {
                foreach ($bases as $base) {
                    if ($base['inscript']) {
                        return true;
                    }
                }
            }

            return false;
        });

        $app['registration.optional-fields'] = $app->share(function (Application $app) {
            return array(
                'login'=> array(
                    'label'       => _('admin::compte-utilisateur identifiant'),
                    'type'        => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new NewLogin($app),
                    )
                ),
                'gender' => array(
                    'label'   => _('admin::compte-utilisateur sexe'),
                    'type'    => 'choice',
                    'multiple' => false,
                    'expanded' => false,
                    'choices' => array(
                        '0' => _('admin::compte-utilisateur:sexe: mademoiselle'),
                        '1' => _('admin::compte-utilisateur:sexe: madame'),
                        '2' => _('admin::compte-utilisateur:sexe: monsieur'),
                    )
                ),
                'firstname' => array(
                    'label' => _('admin::compte-utilisateur prenom'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'lastname' => array(
                    'label' => _('admin::compte-utilisateur nom'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'address' => array(
                    'label' => _('admin::compte-utilisateur adresse'),
                    'type' => 'textarea',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'zipcode' => array(
                    'label' => _('admin::compte-utilisateur code postal'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'geonameid' => array(
                    'label' => _('admin::compte-utilisateur ville'),
                    'type' => new \Alchemy\Phrasea\Form\Type\GeonameType(),
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'position' => array(
                    'label' => _('admin::compte-utilisateur poste'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'company' => array(
                    'label' => _('admin::compte-utilisateur societe'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'job' => array(
                    'label' => _('admin::compte-utilisateur activite'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'tel' => array(
                    'label' => _('admin::compte-utilisateur tel'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
                'fax' => array(
                    'label' => _('admin::compte-utilisateur fax'),
                    'type' => 'text',
                    'constraints' => array(
                        new Assert\NotBlank(),
                    )
                ),
            );
        });
    }

    public function boot(Application $app)
    {
    }
}
