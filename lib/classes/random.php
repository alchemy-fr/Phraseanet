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

class random
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return Boolean
     */
    public function cleanTokens()
    {
        try {
            $conn = $this->app->getApplicationBox()->get_connection();

            $date = new DateTime();
            $date = $this->app['date-formatter']->format_mysql($date);

            $sql = 'SELECT * FROM tokens WHERE expire_on < :date
              AND datas IS NOT NULL AND (type="download" OR type="email")';
            $stmt = $conn->executeQuery($sql, [':date' => $date]);
            $rs = $stmt->fetchAllAssociative();
            foreach ($rs as $row) {
                switch ($row['type']) {
                    case 'download':
                    case 'email':
                        $file = $this->app['tmp.download.path'].'/'.$row['value'].'.zip';
                        if (is_file($file))
                            unlink($file);
                        break;
                }
            }

            $sql = 'DELETE FROM tokens WHERE expire_on < :date and (type="download" OR type="email")';
            $stmt = $conn->prepare($sql);
            $stmt->executeStatement([':date' => $date]);

            return true;
        } catch (\Exception $e) {

        }

        return false;
    }
}
