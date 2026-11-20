<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Psr\Log\LoggerInterface;

/**
 * 4.2.1 introduces enforcement of the "API access" right (User::granted_api):
 * only users holding it (or admins) may reach the "Developers" area and create
 * API keys / applications.
 *
 * To avoid locking out people who already rely on the API when they upgrade,
 * this patch grants the right to:
 *   - every administrator,
 *   - every user who OWNS at least one API application.
 *
 * The granted accounts are logged at the end of the patch so the operator can
 * review them after the upgrade.
 */
class patch_421PHRASApiAccess implements patchInterface
{
    /** @var string */
    private $release = '4.2.1';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /** @var Application */
    private $app;

    /** @var LoggerInterface */
    private $logger;

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
    public function getDoctrineMigrations()
    {
        // The granted_api column already exists; this patch only seeds its values.
        return [];
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
    public function apply(base $base, Application $app)
    {
        $this->app = $app;
        $this->logger = $app['logger'];

        if ($base->get_base_type() !== base::APPLICATION_BOX) {
            return true;
        }

        $conn = $base->get_connection();

        // Grant API access to admins and to owners of an API application, so the
        // newly-enforced right does not lock them out of the developers area.
        $conn->executeStatement(
            "UPDATE Users SET granted_api = 1
             WHERE admin = 1
                OR id IN (SELECT DISTINCT creator_id FROM ApiApplications WHERE creator_id IS NOT NULL)"
        );

        // List the granted accounts for post-upgrade review.
        $rows = $conn->executeQuery(
            "SELECT u.id, u.login, u.email, u.admin,
                    (u.id IN (SELECT DISTINCT creator_id FROM ApiApplications WHERE creator_id IS NOT NULL)) AS owns_app
             FROM Users u
             WHERE u.granted_api = 1
             ORDER BY u.id"
        )->fetchAllAssociative();

        $this->logger->info(sprintf(
            '[patch 4.2.1 / API access] %d account(s) were granted API access — please review:',
            count($rows)
        ));

        foreach ($rows as $row) {
            $reasons = [];
            if (!empty($row['admin'])) {
                $reasons[] = 'admin';
            }
            if (!empty($row['owns_app'])) {
                $reasons[] = 'owns an API application';
            }

            $this->logger->info(sprintf(
                '[patch 4.2.1 / API access]   - id=%s login=%s email=%s (%s)',
                $row['id'],
                $row['login'],
                $row['email'],
                implode(', ', $reasons) ?: 'granted'
            ));
        }

        return true;
    }
}
