<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\User;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\Helper\Helper;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordSetup;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\HttpFoundation\Request;

class Manage extends Helper
{
    use NotifierAware;

    /** @var array */
    protected $query_parms;

    /** @var int */
    protected $usr_id;

    public function __construct(Application $app, Request $Request)
    {
        parent::__construct($app, $Request);

        $this->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'));
    }

    /**
     * @return User[]
     */
    public function export()
    {
        $request = $this->request;

        $offset_start = (int) $request->get('offset_start');
        $offset_start = $offset_start < 0 ? 0 : $offset_start;

        $this->query_parms = [
            'inactives' => $request->get('inactives'),
            'like_field' => $request->get('like_field'),
            'like_value' => $request->get('like_value'),
            'sbas_id' => $request->get('sbas_id'),
            'base_id' => $request->get('base_id'),
            'last_model' => $this->request->get('last_model'),
            'srt' => $request->get("srt", \User_Query::SORT_CREATIONDATE),
            'ord' => $request->get("ord", \User_Query::ORD_DESC),
            'offset_start' => $offset_start,
        ];

        $query = $this->app['phraseanet.user-query'];

        if (is_array($this->query_parms['base_id']))
            $query->on_base_ids($this->query_parms['base_id']);
        elseif (is_array($this->query_parms['sbas_id']))
            $query->on_sbas_ids($this->query_parms['sbas_id']);

        $results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->last_model_is($this->query_parms['last_model'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(false)
            ->on_bases_where_i_am($this->app->getAclForUser($this->app->getAuthenticatedUser()), [\ACL::CANADMIN])
            ->execute();

        return $results->get_results();
    }

    public function search()
    {
        $offset_start = (int) $this->request->get('offset_start');
        $offset_start = $offset_start < 0 ? 0 : $offset_start;
        $results_quantity = (int) $this->request->get('per_page');
        $results_quantity = ($results_quantity < 10 || $results_quantity > 50) ? 20 : $results_quantity;

        $this->query_parms = [
            'inactives' => $this->request->get('inactives'),
            'like_field' => $this->request->get('like_field'),
            'like_value' => $this->request->get('like_value'),
            'sbas_id' => $this->request->get('sbas_id'),
            'base_id' => $this->request->get('base_id'),
            'last_model' => $this->request->get('last_model'),
            'srt' => $this->request->get("srt", \User_Query::SORT_CREATIONDATE),
            'ord' => $this->request->get("ord", \User_Query::ORD_DESC),
            'per_page' => $results_quantity,
            'offset_start' => $offset_start,
        ];

        $query = $this->app['phraseanet.user-query'];

        if (is_array($this->query_parms['base_id']))
            $query->on_base_ids($this->query_parms['base_id']);
        elseif (is_array($this->query_parms['sbas_id']))
            $query->on_sbas_ids($this->query_parms['sbas_id']);

        $results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->last_model_is($this->query_parms['last_model'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(true)
            ->on_bases_where_i_am($this->app->getAclForUser($this->app->getAuthenticatedUser()), [\ACL::CANADMIN])
            ->limit($offset_start, $results_quantity)
            ->execute();

        if (null === $invite = $this->app['repo.users']->findByLogin(User::USER_GUEST)) {
            $invite = $this->app['manipulator.user']->createUser(User::USER_GUEST, User::USER_GUEST);
        }

        if (null === $autoregister = $this->app['repo.users']->findByLogin(User::USER_AUTOREGISTER)) {
            $autoregister = $this->app['manipulator.user']->createUser(User::USER_AUTOREGISTER, User::USER_AUTOREGISTER);
        }

        foreach ($this->query_parms as $k => $v) {
            if (is_null($v))
                $this->query_parms[$k] = false;
        }

        $query = $this->app['phraseanet.user-query'];
        $templates = $query
                ->only_templates(true)
                ->execute()->get_results();

        return [
            'users'             => $results,
            'parm'              => $this->query_parms,
            'invite_user'       => $invite,
            'autoregister_user' => $autoregister,
            'templates'         => $templates
        ];
    }

    public function createNewUser()
    {
        $email = $this->request->get('value');

        if ( ! \Swift_Validate::email($email)) {
            throw new \Exception_InvalidArgument('Invalid mail address');
        }

        if (null === $createdUser = $this->app['repo.users']->findByEmail($email)) {
            $createdUser = $this->app['manipulator.user']->createUser($email, $this->app['random.medium']->generateString(128), $email);

            if ((bool) $this->request->get('send_credentials', false)) {
                $this->sendPasswordSetupMail($createdUser);
            }

            if ((bool) $this->request->get('validate_mail', false)) {
                $createdUser->setMailLocked(true);

                $this->sendAccountUnlockEmail($createdUser);
            }
        }

        $this->usr_id = $createdUser->getId();

        return $createdUser;
    }

    public function createTemplate()
    {
        $name = $this->request->get('value');

        if (trim($name) === '') {
            throw new \Exception_InvalidArgument('Invalid template name');
        }

        $created_user = $this->app['manipulator.user']->createTemplate($name, $this->app->getAuthenticatedUser());
        $this->usr_id = $this->app->getAuthenticatedUser()->getId();

        return $created_user;
    }

    public function sendAccountUnlockEmail(User $user)
    {
        $receiver = Receiver::fromUser($user);

        $token = $this->app['manipulator.token']->createAccountUnlockToken($user);

        $mail = MailRequestEmailConfirmation::create($this->app, $receiver);
        $mail->setButtonUrl($this->app->url('login_register_confirm', ['code' => $token->getValue()]));
        $mail->setExpiration($token->getExpiration());

        $this->deliver($mail);
    }

    public function sendPasswordSetupMail(User $user)
    {
        $receiver = Receiver::fromUser($user);

        $token = $this->app['manipulator.token']->createResetPasswordToken($user);

        $mail = MailRequestPasswordSetup::create($this->app, $receiver);
        $mail->setButtonUrl($this->app->url('login_renew_password', ['token' => $token->getValue()]));
        $mail->setLogin($user->getLogin());

        $this->deliver($mail);
    }
}
