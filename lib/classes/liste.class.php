<?php

use Alchemy\Phrasea\Application;

class liste
{

    public static function filter(Application $app, $lst)
    {
        if ( ! is_array($lst))
            explode(';', $lst);

        $okbrec = array();

        foreach ($lst as $basrec) {
            $basrec = explode("_", $basrec);
            if ( ! $basrec || count($basrec) != 2) {
                continue;
            }
            try {
                $record = new record_adapter($app, $basrec[0], $basrec[1]);
            } catch (Exception $e) {
                continue;
            }

            if ($app['phraseanet.user']->ACL()->has_hd_grant($record)) {
                $okbrec[] = implode('_', $basrec);
                continue;
            }
            if ($app['phraseanet.user']->ACL()->has_preview_grant($record)) {
                $okbrec[] = implode('_', $basrec);
                continue;
            }

            if ( ! $app['phraseanet.user']->ACL()->has_access_to_base($record->get_base_id()))
                continue;

            try {
                $connsbas = connection::getPDOConnection($app, $basrec[0]);

                $sql = 'SELECT record_id FROM record WHERE ((status ^ ' . $app['phraseanet.user']->ACL()->get_mask_xor($record->get_base_id()) . ')
                    & ' . $app['phraseanet.user']->ACL()->get_mask_and($record->get_base_id()) . ')=0' .
                    ' AND record_id = :record_id';

                $stmt = $connsbas->prepare($sql);
                $stmt->execute(array(':record_id' => $basrec[1]));

                if ($stmt->rowCount() > 0)
                    $okbrec[] = implode('_', $basrec);

                $stmt->closeCursor();
            } catch (Exception $e) {

            }
        }

        return $okbrec;
    }
}
