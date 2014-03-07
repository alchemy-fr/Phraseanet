<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Doctrine\ORM\EntityManager;

class patch_390alpha17a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.17';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['api'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $this->fillApplicationTable($app['EM']);
        $this->fillAccountTable($app['EM']);
        $this->fillLogTable($app['EM']);
        $this->fillCodeTable($app['EM']);
        $this->fillRefreshTokenTable($app['EM']);
        $this->fillOauthTokenTable($app['EM']);
    }

    private function fillApplicationTable(EntityManager $em)
    {
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiApplications
            (
                id,           `type`,     `name`,         description,    website
                created,      updated,    client_id,      client_secret,  nonce
                redirect_uri, activated,  grant_password, creator_id

            )
            (
                SELECT
                application_id, `type`,         `name`,         description,    website,
                created_on,     last_modified,  client_id,      client_secret,  nonce,
                redirect_uri,   activated,      grant_password, creator
                FROM api_applications
                INNER JOIN Users ON (Users.id = api_accounts.usr_id)
            )'
        );
    }

    private function fillAccountTable(EntityManager $em)
    {
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiAccounts
            (
                id,           user_id,  revoked
                api_version,  created,  application_id

            )
            (
                SELECT
                api_account_id, usr_id,   revoked,
                api_version,    created,  application_id
                FROM api_accounts
                INNER JOIN Users ON (Users.id = api_accounts.usr_id)
                INNER JOIN api_applications ON (api_accounts.application_id = api_applications.application_id)
            )'
        );
    }

    private function fillLogTable(EntityManager $em)
    {
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiLogs
            (
                id,         account_id,     route,    error_message
                created,    status_code,    format,   resource,
                general,    aspect,         `action`, error_code,

            )
            (
                SELECT
                api_log_id,       api_account_id,       api_log_route,  api_log_error_message
                api_log_date,     api_log_status_code,  api_log_format, api_log_resource,
                api_log_general,  api_log_aspect,       api_log_action, api_log_error_code
                FROM api_log
                INNER JOIN api_accounts ON (api_accounts.api_account_id = api_log.api_account_id)
            )'
        );
    }

    private function fillCodeTable(EntityManager $em)
    {
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiOauthCodes
            (
                code,       account_id,     redirect_uri,    expires
                scope,      created,        updated

            )
            (
                SELECT
                code,   api_account_id, redirect_uri, expires
                scope,  NOW(),          NOW()
                FROM api_oauth_codes
                INNER JOIN api_accounts ON (api_accounts.api_account_id = api_oauth_codes.api_account_id)
            )'
        );
    }

    private function fillRefreshTokenTable(EntityManager $em)
    {
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiOauthRefreshTokens
            (
                refresh_token,  account_id,     expires
                scope,          created,        updated

            )
            (
                SELECT
                refresh_token,  api_account_id, expires
                scope,          NOW(),          NOW()
                FROM api_oauth_refresh_tokens
                INNER JOIN api_accounts ON (api_accounts.api_account_id = api_oauth_refresh_tokens.api_account_id)
            )'
        );
    }


    private function fillOauthTokenTable(EntityManager $em)
    {
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiOauthTokens
            (
                oauth_token,  account_id,     session_id, expires
                scope,        created,        updated

            )
            (
                SELECT
                oauth_token,  api_account_id, session_id, expires
                scope,          NOW(),          NOW()
                FROM api_oauth_tokens
                INNER JOIN api_accounts ON (api_accounts.api_account_id = api_oauth_tokens.api_account_id)
            )'
        );
    }
}
