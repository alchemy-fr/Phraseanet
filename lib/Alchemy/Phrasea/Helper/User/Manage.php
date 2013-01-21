<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Helper\User;

use Alchemy\Phrasea\Helper\Helper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
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
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(false)
            ->on_bases_where_i_am($this->app['phraseanet.user']->ACL(), array('canadmin'))
            ->execute();

        return $this->results->get_results();
    }

    public function search()
    {
        $request = $this->request;

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
            ->get_inactives($this->query_parms['inactives'])
            ->include_templates(true)
            ->on_bases_where_i_am($this->app['phraseanet.user']->ACL(), array('canadmin'))
            ->limit($offset_start, $results_quantity)
            ->execute();

        try {
            $invite_id = \User_Adapter::get_usr_id_from_login($this->app, 'invite');
            $invite = \User_Adapter::getInstance($invite_id, $this->app);
        } catch (\Exception $e) {
            $invite = \User_Adapter::create($this->app, 'invite', 'invite', '', false);
        }

        try {
            $autoregister_id = \User_Adapter::get_usr_id_from_login($this->app, 'autoregister');
            $autoregister = \User_Adapter::getInstance($autoregister_id, $this->app);
        } catch (\Exception $e) {
            $autoregister = \User_Adapter::create($this->app, 'autoregister', 'autoregister', '', false);
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

        if (!\mail::validateEmail($email)) {
            throw new \Exception_InvalidArgument(_('Invalid mail address'));
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

            $createdUser = \User_Adapter::create($this->app, $email, \random::generatePassword(16), $email, false, false);
            /* @var $createdUser \User_Adapter */
            if ($validateMail) {
                $createdUser->set_mail_locked(true);
                \mail::mail_confirmation($this->app, $email, $createdUser->get_id());
            }

            if ($sendCredentials) {
                $urlToken = \random::getUrlToken($this->app, \random::TYPE_PASSWORD, $createdUser->get_id());

                if (false !== $urlToken) {
                    $url =  $this->app['url_generator']->generate('login_forgot_password', array('token' => $urlToken), true);
                    \mail::send_credentials($this->app, $url, $createdUser->get_login(), $createdUser->get_email());
                }
            }

            $this->usr_id = $createdUser->get_id();
        } else {
            $this->usr_id = $row['usr_id'];
            $createdUser = \User_Adapter::getInstance($this->usr_id, $this->app);
        }

        return $createdUser;
    }

    public function create_template()
    {
        $name = $this->request->get('value');

        if (trim($name) === '') {
            throw new \Exception_InvalidArgument(_('Invalid template name'));
        }

        $created_user = \User_Adapter::create($this->app, $name, \random::generatePassword(16), null, false, false);
        $created_user->set_template($this->app['phraseanet.user']);
        $this->usr_id = $this->app['phraseanet.user']->get_id();

        return $created_user;
    }
}
