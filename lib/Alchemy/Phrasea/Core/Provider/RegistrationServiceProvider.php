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
            return $app['conf']->get('registration-fields', []);
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
            return [
                'login'=> [
                    'label'       => _('admin::compte-utilisateur identifiant'),
                    'type'        => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                        new NewLogin($app),
                    ]
                ],
                'gender' => [
                    'label'   => _('admin::compte-utilisateur sexe'),
                    'type'    => 'choice',
                    'multiple' => false,
                    'expanded' => false,
                    'choices' => [
                        '0' => _('admin::compte-utilisateur:sexe: mademoiselle'),
                        '1' => _('admin::compte-utilisateur:sexe: madame'),
                        '2' => _('admin::compte-utilisateur:sexe: monsieur'),
                    ]
                ],
                'firstname' => [
                    'label' => _('admin::compte-utilisateur prenom'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'lastname' => [
                    'label' => _('admin::compte-utilisateur nom'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'address' => [
                    'label' => _('admin::compte-utilisateur adresse'),
                    'type' => 'textarea',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'zipcode' => [
                    'label' => _('admin::compte-utilisateur code postal'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'geonameid' => [
                    'label' => _('admin::compte-utilisateur ville'),
                    'type' => new \Alchemy\Phrasea\Form\Type\GeonameType(),
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'position' => [
                    'label' => _('admin::compte-utilisateur poste'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'company' => [
                    'label' => _('admin::compte-utilisateur societe'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'job' => [
                    'label' => _('admin::compte-utilisateur activite'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'tel' => [
                    'label' => _('admin::compte-utilisateur tel'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'fax' => [
                    'label' => _('admin::compte-utilisateur fax'),
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
            ];
        });
    }

    public function boot(Application $app)
    {
    }
}
