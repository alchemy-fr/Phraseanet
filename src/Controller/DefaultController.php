<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Driver\Connection;

class DefaultController
{
    public function index(Connection $connection)
    {

        $ab_tables = $connection->fetchAll('SHOW TABLES');

        var_dump($ab_tables);

        return new Response(
            '<html><body>Hello world</body></html>'
        );
    }
}