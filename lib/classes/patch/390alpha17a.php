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
        return ['20140324000001'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $this->fillApplicationTable($app['orm.em']);
        $this->fillAccountTable($app['orm.em']);
        $this->fillLogTable($app['orm.em']);
        $this->fillCodeTable($app['orm.em']);
        $this->fillRefreshTokenTable($app['orm.em']);
        $this->fillOauthTokenTable($app['orm.em']);
        $this->setOauthTokenExpiresToNull($app['orm.em']);
        $this->updateLogsTable($app['orm.em']);
    }

    private function fillApplicationTable(EntityManager $em)
    {
        if (false === $this->tableExists($em, 'api_applications')) {
            return true;
        }
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiApplications
            (
                id,           `type`,     `name`,         description,    website,
                created,      updated,    client_id,      client_secret,  nonce,
                redirect_uri, activated,  grant_password, creator_id

            )
            (
                SELECT
                a.application_id, a.`type`,         a.`name`,         a.description,    a.website,
                a.created_on,     a.last_modified,  a.client_id,      a.client_secret,  a.nonce,
                a.redirect_uri,   a.activated,      a.grant_password, creator
                FROM api_applications a
                LEFT JOIN Users u ON (u.id = a.creator)
                WHERE u.id IS NOT NULL
                OR a.`name` = "'. \API_OAuth2_Application_Navigator::CLIENT_NAME .'"
                OR a.`name` = "'. \API_OAuth2_Application_OfficePlugin::CLIENT_NAME .'"
            )'
        );
    }

    private function fillAccountTable(EntityManager $em)
    {
        if (false === $this->tableExists($em, 'api_accounts')) {
            return true;
        }
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiAccounts
            (
                id,           user_id,  revoked,
                api_version,  created,  application_id

            )
            (
                SELECT
                a.api_account_id, a.usr_id,   a.revoked,
                a.api_version,    a.created,  a.application_id
                FROM api_accounts a
                INNER JOIN Users ON (Users.id = a.usr_id)
                INNER JOIN api_applications b ON (a.application_id = b.application_id)
            )'
        );
    }

    private function fillLogTable(EntityManager $em)
    {
        if (false === $this->tableExists($em, 'api_accounts')) {
            return true;
        }
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiLogs
            (
                id,         account_id,     route,    error_message,
                created,    status_code,    format,   resource,
                general,    aspect,         `action`, error_code

            )
            (
                SELECT
                a.api_log_id,       a.api_account_id,       a.api_log_route,  a.api_log_error_message,
                a.api_log_date,     a.api_log_status_code,  a.api_log_format, a.api_log_resource,
                a.api_log_general,  a.api_log_aspect,       a.api_log_action, a.api_log_error_code
                FROM api_logs a
                INNER JOIN api_accounts b ON (b.api_account_id = a.api_account_id)
            )'
        );
    }

    private function fillCodeTable(EntityManager $em)
    {
        if (false === $this->tableExists($em, 'api_oauth_codes')) {
            return true;
        }
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiOauthCodes
            (
                code,       account_id,     redirect_uri,    expires,
                scope,      created,        updated

            )
            (
                SELECT
                a.code,   a.api_account_id, a.redirect_uri, a.expires,
                a.scope,  NOW(),          NOW()
                FROM api_oauth_codes a
                INNER JOIN api_accounts b ON (b.api_account_id = a.api_account_id)
            )'
        );
    }

    private function fillRefreshTokenTable(EntityManager $em)
    {
        if (false === $this->tableExists($em, 'api_oauth_refresh_tokens')) {
            return true;
        }
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiOauthRefreshTokens
            (
                refresh_token,  account_id,     expires,
                scope,          created,        updated

            )
            (
                SELECT
                a.refresh_token,  a.api_account_id, a.expires,
                a.scope,          NOW(),          NOW()
                FROM api_oauth_refresh_tokens a
                INNER JOIN api_accounts b ON (b.api_account_id = a.api_account_id)
            )'
        );
    }

    private function fillOauthTokenTable(EntityManager $em)
    {
        if (false === $this->tableExists($em, 'api_oauth_tokens')) {
            return true;
        }
        $em->getConnection()->executeUpdate(
            'INSERT INTO ApiOauthTokens
            (
                oauth_token,  account_id,     session_id, expires,
                scope,        created,        updated,    last_used

            )
            (
                SELECT
                a.oauth_token,  a.api_account_id, a.session_id, expires,
                a.scope,          NOW(),          NOW(),        NOW()
                FROM api_oauth_tokens a
                INNER JOIN api_accounts b ON (b.api_account_id = a.api_account_id)
            )'
        );
    }

    private function setOauthTokenExpiresToNull(EntityManager $em)
    {
        $qb = $em->createQueryBuilder();
        $q = $qb->update('Phraseanet:ApiOauthToken', 'a')
            ->set('a.expires', $qb->expr()->literal(null))
            ->getQuery();
        $q->execute();
    }

    /**
     * Update ApiLogs Table
     *
     * before :
     *   +--------------------+
     *   | route              |
     *   +--------------------+
     *   | GET /databox/list/ |
     *   +--------------------+
     *
     * after :
     *   +----------------+--------+
     *   | route          | method |
     *   +----------------+--------+
     *   | /databox/list/ | GET    |
     *   +----------------+--------+
     *
     */
    private function updateLogsTable(EntityManager $em)
    {
        $em->getConnection()->executeUpdate("
            UPDATE `ApiLogs`
            SET method = SUBSTRING_INDEX(SUBSTRING_INDEX(route, ' ', 1), ' ', -1),
                route = SUBSTRING_INDEX(SUBSTRING_INDEX(route, ' ', 2), ' ', -1)
        ");
    }
}
