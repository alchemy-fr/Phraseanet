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

        $this->query_parms = array(
            'inactives'    => $request->get('inactives')
            , 'like_field'   => $request->get('like_field')
            , 'like_value'   => $request->get('like_value')
            , 'sbas_id'      => $request->get('sbas_id')
            , 'base_id'      => $request->get('base_id')
            , 'last_model'   => $this->request->get('last_model')
            , 'srt'          => $request->get("srt", \User_Query::SORT_CREATIONDATE)
            , 'ord'          => $request->get("ord", \User_Query::ORD_DESC)
            , 'offset_start' => 0
        );

        $query = new \User_Query($this->app);

        if (is_array($this->query_parms['base_id']))
            $query->on_base_ids($this->query_parms['base_id']);
        elseif (is_array($this->query_parms['sbas_id']))
            $query->on_sbas_ids($this->query_parms['sbas_id']);

        $this->results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->last_model_is($this->query_parms['last_model'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(false)
            ->on_bases_where_i_am($this->app['acl']->get($this->app['authentication']->getUser()), array('canadmin'))
            ->execute();

        return $this->results->get_results();
    }

    public function search()
    {
        $offset_start = (int) $this->request->get('offset_start');
        $offset_start = $offset_start < 0 ? 0 : $offset_start;
        $results_quantity = (int) $this->request->get('per_page');
        $results_quantity = ($results_quantity < 10 || $results_quantity > 50) ? 20 : $results_quantity;

        $this->query_parms = array(
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
        );

        $query = new \User_Query($this->app);

        if (is_array($this->query_parms['base_id']))
            $query->on_base_ids($this->query_parms['base_id']);
        elseif (is_array($this->query_parms['sbas_id']))
            $query->on_sbas_ids($this->query_parms['sbas_id']);

        $this->results = $query->sort_by($this->query_parms["srt"], $this->query_parms["ord"])
            ->like($this->query_parms['like_field'], $this->query_parms['like_value'])
            ->last_model_is($this->query_parms['last_model'])
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(true)
            ->on_bases_where_i_am($this->app['acl']->get($this->app['authentication']->getUser()), array('canadmin'))
            ->limit($offset_start, $results_quantity)
            ->execute();

        if (null === $invite = $this->app['manipulator.user']->getRepository()->findByLogin(User::USER_GUEST)) {
            $this->app['manipulator.user']->createUser(User::USER_GUEST, User::USER_GUEST);
        }

        if (null == $autoregister = $this->app['manipulator.user']->getRepository()->findByLogin(User::USER_AUTOREGISTER)) {
            $this->app['manipulator.user']->createUser(User::USER_AUTOREGISTER, User::USER_AUTOREGISTER);
        }

        foreach ($this->query_parms as $k => $v) {
            if (is_null($v))
                $this->query_parms[$k] = false;
        }

        $query = new \User_Query($this->app);
        $templates = $query
                ->only_templates(true)
                ->execute()->get_results();

        return array(
            'users'             => $this->results,
            'parm'              => $this->query_parms,
            'invite_user'       => $invite,
            'autoregister_user' => $autoregister,
            'templates'         => $templates
        );
    }

    public function create_newuser()
    {
        $email = $this->request->get('value');

        if ( ! \Swift_Validate::email($email)) {
            throw new \Exception_InvalidArgument('Invalid mail address');
        }

        $conn = $this->app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT usr_id FROM usr WHERE usr_mail = :email';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':email' => $email));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $count = count($row);

        if (!is_array($row) || $count == 0) {
            $sendCredentials = !!$this->request->get('send_credentials', false);
            $validateMail = !!$this->request->get('validate_mail', false);

            $createdUser = $this->app['manipulator.user']->createUser($email, \random::generatePassword(16), $email);

            $receiver = null;
            try {
                $receiver = Receiver::fromUser($createdUser);
            } catch (InvalidArgumentException $e) {

            }

            if ($sendCredentials) {
                $urlToken = $this->app['tokens']->getUrlToken(\random::TYPE_PASSWORD, $createdUser->getId());

                if ($receiver && false !== $urlToken) {
                    $url = $this->app->url('login_renew_password', array('token' => $urlToken));
                    $mail = MailRequestPasswordSetup::create($this->app, $receiver, null, '', $url);
                    $mail->setLogin($createdUser->getLogin());
                    $this->app['notification.deliverer']->deliver($mail);
                }
            }

            if ($validateMail) {
                $createdUser->set_mail_locked(true);

                if ($receiver) {
                    $expire = new \DateTime('+3 days');
                    $token = $this->app['tokens']->getUrlToken(\random::TYPE_PASSWORD, $createdUser->getId(), $expire, $createdUser->getEmail());
                    $url = $this->app->url('login_register_confirm', array('code' => $token));

                    $mail = MailRequestEmailConfirmation::create($this->app, $receiver, null, '', $url, $expire);
                    $this->app['notification.deliverer']->deliver($mail);
                }
            }

            $this->usr_id = $createdUser->getId();
        } else {
            $this->usr_id = $row['usr_id'];
            $createdUser = $this->app['manipulator.user']->getRepository()->find($this->usr_id);
        }

        return $createdUser;
    }

    public function create_template()
    {
        $name = $this->request->get('value');

        if (trim($name) === '') {
            throw new \Exception_InvalidArgument('Invalid template name');
        }

        $created_user = $this->app['manipulator.user']->getRepository()->find($name, \random::generatePassword(16));
        $created_user->set_template($this->app['authentication']->getUser());
        $this->usr_id = $this->app['authentication']->getUser()->getId();

        return $created_user;
    }
}
