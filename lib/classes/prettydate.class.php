<?php
/*
 * JavaScript Pretty Date
 * Copyright (c) 2008 John Resig (jquery.com)
 * Licensed under the MIT license.
 */

// Ported to PHP >= 5.1 by Zach Leatherman (zachleat.com)
// Slight modification denoted below to handle months and years.
class prettydate
{
    public static function getStringResolved($date, $compareTo = NULL)
    {
        if(!is_null($compareTo)) {
            $compareTo = new DateTime($compareTo);
        }
        return self::getString(new DateTime($date), $compareTo);
    }

    public static function getString(DateTime $date, DateTime $compareTo = NULL)
    {
        if(is_null($compareTo)) {
            $compareTo = new DateTime('now');
        }
        $diff = $compareTo->format('U') - $date->format('U');
        $dayDiff = floor($diff / 86400);

        if(is_nan($dayDiff) || $dayDiff < 0) {
            return '';
        }
                
        if($dayDiff == 0) {
            if($diff < 60) {
                return _('phraseanet::temps:: a l\'instant');
            } elseif($diff < 120) {
                return _('phraseanet::temps:: il y a une minute');
            } elseif($diff < 3600) {
                return sprintf(_('phraseanet::temps:: il y a %d minutes'),floor($diff/60));
            } elseif($diff < 7200) {
                return _('phraseanet::temps:: il y a une heure');
            } elseif($diff < 86400) {
                return sprintf(_('phraseanet::temps:: il y a %d heures'),floor($diff/3600));
            }
        } elseif($dayDiff == 1) {
            return _('phraseanet::temps:: hier');
        } elseif($dayDiff < 7) {
            return sprintf(_('phraseanet::temps:: il y a %d jours'),$dayDiff);
        } elseif($dayDiff == 7) {
            return _('phraseanet::temps:: il y a une semaine');
        } elseif($dayDiff < (7*6)) { // Modifications Start Here
            // 6 weeks at most
            return sprintf(_('phraseanet::temps:: il y a %d semaines'),ceil($dayDiff/7));
        } elseif($dayDiff < 365) {
            return sprintf(_('phraseanet::temps:: il y a %d mois'),ceil($dayDiff/(365/12)));
        } else {
            $years = round($dayDiff/365);
            if($years === 1)
	            return _('phraseanet::temps:: il y a un an');
	        else
            	return sprintf(_('phraseanet::temps:: il y a %d ans'),$years);
        }
    }
} 

?>