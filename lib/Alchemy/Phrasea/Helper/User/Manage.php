<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\User;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Helper\Helper;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRequestPasswordSetup;
use Alchemy\Phrasea\Notification\Mail\MailRequestEmailConfirmation;
use Alchemy\Phrasea\Model\Entities\User;

class Manage extends Helper
{
    /**
     *
     * @var array
     */
    protected $results;

    /**
     *
     * @var array
     */
    protected $query_parms;

    /**
     *
     * @var int
     */
    protected $usr_id;

    public function export()
    {
        $request = $this->request;

        $offset_start = (int) $request->get('offset_start');
        $offset_start = $offset_start < 0 ? 0 : $offset_start;

        $this->query_parms = [
            'inactives'    => $request->get('inactives')
            , 'like_field'   => $request->get('like_field')
            , 'like_value'   => $request->get('like_value')
            , 'sbas_id'      => $request->get('sbas_id')
            , 'base_id'      => $request->get('base_id')
            , 'last_model'   => $this->request->get('last_model')
            , 'srt'          => $request->get("srt", \User_Query::SORT_CREATIONDATE)
            , 'ord'          => $request->get("ord", \User_Query::ORD_DESC)
            , 'offset_start' => 0
        ];

        $query = $this->app['phraseanet.user-query'];

        if (is_array($this->query_parms['base_id']))
            $query->on_base_ids($this->query_parms['base_id']);
        elseif (is_array($this->query_parms['sbas_id']))
            $query->on_sbas_ids($this->query_parms['sbas_id']);

        $this->results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->last_model_is($this->query_parms['last_model'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(false)
            ->on_bases_where_i_am($this->app['acl']->get($this->app['authentication']->getUser()), ['canadmin'])
            ->execute();

        return $this->results->get_results();
    }

    public function search()
    {
        $offset_start = (int) $this->request->get('offset_start');
        $offset_start = $offset_start < 0 ? 0 : $offset_start;
        $results_quantity = (int) $this->request->get('per_page');
        $results_quantity = ($results_quantity < 10 || $results_quantity > 50) ? 20 : $results_quantity;

        $this->query_parms = [
            'inactives'    => $this->request->get('inactives')
            , 'like_field'   => $this->request->get('like_field')
            , 'like_value'   => $this->request->get('like_value')
            , 'sbas_id'      => $this->request->get('sbas_id')
            , 'base_id'      => $this->request->get('base_id')
            , 'last_model'   => $this->request->get('last_model')
            , 'srt'          => $this->request->get("srt", \User_Query::SORT_CREATIONDATE)
            , 'ord'          => $this->request->get("ord", \User_Query::ORD_DESC)
            , 'per_page'     => $results_quantity
            , 'offset_start' => $offset_start
        ];

        $query = $this->app['phraseanet.user-query'];

        if (is_array($this->query_parms['base_id']))
            $query->on_base_ids($this->query_parms['base_id']);
        elseif (is_array($this->query_parms['sbas_id']))
            $query->on_sbas_ids($this->query_parms['sbas_id']);

        $this->results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->last_model_is($this->query_parms['last_model'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(true)
            ->on_bases_where_i_am($this->app['acl']->get($this->app['authentication']->getUser()), ['canadmin'])
            ->limit($offset_start, $results_quantity)
            ->execute();

        if (null === $invite = $this->app['repo.users']->findByLogin(User::USER_GUEST)) {
            $invite = $this->app['manipulator.user']->createUser(User::USER_GUEST, User::USER_GUEST);
        }

        if (null == $autoregister = $this->app['repo.users']->findByLogin(User::USER_AUTOREGISTER)) {
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
            'users'             => $this->results,
            'parm'              => $this->query_parms,
            'invite_user'       => $invite,
            'autoregister_user' => $autoregister,
            'templates'         => $templates
        ];
    }

    public function create_newuser()
    {
        $email = $this->request->get('value');

        if ( ! \Swift_Validate::email($email)) {
            throw new \Exception_InvalidArgument('Invalid mail address');
        }

        if (null === $createdUser = $this->app['repo.users']->findByEmail($email)) {
            $sendCredentials = !!$this->request->get('send_credentials', false);
            $validateMail = !!$this->request->get('validate_mail', false);

            $createdUser = $this->app['manipulator.user']->createUser($email, $this->app['random.medium']->generateString(128), $email);

            $receiver = null;
            try {
                $receiver = Receiver::fromUser($createdUser);
            } catch (InvalidArgumentException $e) {

            }

            if ($sendCredentials && $receiver) {
                $urlToken = $this->app['manipulator.token']->createResetPasswordToken($createdUser);
                $url = $this->app->url('login_renew_password', ['token' => $urlToken->getValue()]);
                $mail = MailRequestPasswordSetup::create($this->app, $receiver, null, '', $url);
                $mail->setLogin($createdUser->getLogin());
                $this->app['notification.deliverer']->deliver($mail);
            }

            if ($validateMail && $receiver) {
                $createdUser->setMailLocked(true);

                $token = $this->app['manipulator.token']->createAccountUnlockToken($createdUser);
                $url = $this->app->url('login_register_confirm', ['code' => $token]);

                $mail = MailRequestEmailConfirmation::create($this->app, $receiver, null, '', $url, $token->getExpiration());
                $this->app['notification.deliverer']->deliver($mail);
            }
        }

        $this->usr_id = $createdUser->getId();

        return $createdUser;
    }

    public function create_template()
    {
        $name = $this->request->get('value');

        if (trim($name) === '') {
            throw new \Exception_InvalidArgument('Invalid template name');
        }

        $created_user = $this->app['manipulator.user']->createTemplate($name, $this->app['authentication']->getUser());
        $this->usr_id = $this->app['authentication']->getUser()->getId();

        return $created_user;
    }
}
