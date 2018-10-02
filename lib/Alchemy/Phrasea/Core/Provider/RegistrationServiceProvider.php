<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Form\Constraint\NewLogin;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;
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

        $app['registration.manager'] = $app->share(function (Application $app) {
            return new RegistrationManager($app['phraseanet.appbox'], $app['repo.registrations'], $app['locale']);
        });

        $app['registration.optional-fields'] = $app->share(function (Application $app) {
            return [
                'login'=> [
                    'label'       => 'admin::compte-utilisateur identifiant',
                    'type'        => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                        NewLogin::create($app),
                    ]
                ],
                'gender' => [
                    'label'   => 'admin::compte-utilisateur sexe',
                    'type'    => 'choice',
                    'multiple' => false,
                    'expanded' => false,
                    'choices' => [
                        User::GENDER_MISS => 'admin::compte-utilisateur:sexe: mademoiselle',
                        User::GENDER_MRS => 'admin::compte-utilisateur:sexe: madame',
                        User::GENDER_MR => 'admin::compte-utilisateur:sexe: monsieur',
                    ]
                ],
                'firstname' => [
                    'label' => 'admin::compte-utilisateur prenom',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'lastname' => [
                    'label' => 'admin::compte-utilisateur nom',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'address' => [
                    'label' => 'admin::compte-utilisateur adresse',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'zipcode' => [
                    'label' => 'admin::compte-utilisateur code postal',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'geonameid' => [
                    'label' => 'admin::compte-utilisateur ville',
                    'type' => new \Alchemy\Phrasea\Form\Type\GeonameType(),
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'job' => [
                    'label' => 'admin::compte-utilisateur poste',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'company' => [
                    'label' => 'admin::compte-utilisateur societe',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'position' => [
                    'label' => 'admin::compte-utilisateur activite',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'tel' => [
                    'label' => 'admin::compte-utilisateur tel',
                    'type' => 'text',
                    'constraints' => [
                        new Assert\NotBlank(),
                    ]
                ],
                'fax' => [
                    'label' => 'admin::compte-utilisateur fax',
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
