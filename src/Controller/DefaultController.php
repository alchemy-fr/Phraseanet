<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DefaultController extends Controller
{

    public function index()
    {

        $ab_tables = $this->get('doctrine.dbal.appbox_connection')->fetchAll('SHOW TABLES');
        $db_tables = $this->get('doctrine.dbal.databox_connection')->fetchAll('SHOW TABLES');

        $ab_list = '';
        $db_list = '';
        $i = 1;
        $j = 1;

        foreach ($ab_tables as $table){
            $ab_list .=  $i.'. '.$table['Tables_in_ab_master'].'<br>';
            $i++;
        }

        foreach ($db_tables as $table){
            $db_list .=  $j.'. '.$table['Tables_in_db_master'].'<br>';
            $j++;
        }

        return new Response(
            '<html>
                <body>
                    Application box tables:<br><br>
                    '.$ab_list.'
                    <br><br>
                    Databox tables:<br><br>
                    '.$db_list.'
                    <br><br><br><br><br>
                </body>
            </html>'
        );
    }

    public function test()
    {
        return new Response(
            '<html>
                <body>
                   Test page
                </body>
            </html>'
        );

    }

    public function showUsers()
    {

        $query = $this->get('doctrine.dbal.appbox_connection')->fetchAll('SELECT * FROM Users');
        var_dump($query);

        return new Response(
            '<html>
                <body>
                    Created users<br><br>
                </body>
            </html>'
        );
    }
}