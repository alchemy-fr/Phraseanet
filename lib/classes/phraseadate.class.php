<?php
/*
 * JavaScript Pretty Date
 * Copyright (c) 2008 John Resig (jquery.com)
 * Licensed under the MIT license.
 */

// Ported to PHP >= 5.1 by Zach Leatherman (zachleat.com)
// Slight modification denoted below to handle months and years.
class phraseadate
{
	
    public static function getTime(DateTime $date)
    {
		$session = session::getInstance();
		$locale = $session->locale;
		
    	switch($locale)
    	{
    		default:
    		case 'fr_FR':
    		case 'de_DE':
    			$time = $date->format('H:i');
    			break;
    		case 'en_GB':
    			$time = $date->format('h:iA');
    			break;
    	}
    	
    	return $time;
    }
	
    public static function getDate(DateTime $date)
    {
		$session = session::getInstance();
    	$compareTo = new DateTime('now');
        $diff = $compareTo->format('U') - $date->format('U');
        $dayDiff = floor($diff / 86400);

        if(is_nan($dayDiff) || $dayDiff < 0) {
            return '';
        }
		
        if($dayDiff <365) {
            return self::formatDate($date, $session->locale,'DAY_MONTH');
        } else {
        	return self::formatDate($date, $session->locale,'DAY_MONTH_YEAR');
        }
    }
    
    public static function getPrettyString(DateTime $date)
    {
		$session = session::getInstance();
    	$compareTo = new DateTime('now');
        $diff = $compareTo->format('U') - $date->format('U');
        $dayDiff = floor($diff / 86400);

        if(is_nan($dayDiff)  || $dayDiff > 365000) {
            return '';
        }

        $date_string = self::formatDate($date, $session->locale,'DAY_MONTH');
        
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
        } elseif($dayDiff <365 && $dayDiff > 0) {
            return $date_string;
        } else {
        	$date_string_year = self::formatDate($date, $session->locale,'DAY_MONTH_YEAR');
            return $date_string_year;
        }
    }
    
    public static function format_mysql(DateTime $date)
    {
    	return $date->format('Y-m-d H:i:s');
    }
    
    private function formatDate(DateTime $date, $locale,$format)
    {
    	
    	switch($locale)
    	{
    		default:
    		case 'fr_FR':
    			switch($format)
    			{
    				default:
    				case 'DAY_MONTH':
    					$date_formated = strftime("%e %B", $date->format('U'));
    					break;
    				case 'DAY_MONTH_YEAR':
    					$date_formated = strftime("%e %B %Y", $date->format('U'));
    					break;
    			}
    			break;
    		case 'en_GB':
    			switch($format)
    			{
    				default:
    				case 'DAY_MONTH':
    					$date_formated = strftime("%B %e", $date->format('U'));
    					break;
    				case 'DAY_MONTH_YEAR':
    					$date_formated = strftime("%B %e %Y", $date->format('U'));
    					break;
    			}
    			break;
    		case 'de_DE':
    			switch($format)
    			{
    				default:
    				case 'DAY_MONTH':
    					$date_formated = strftime("%e. %B", $date->format('U'));
    					break;
    				case 'DAY_MONTH_YEAR':
    					$date_formated = strftime("%e. %B %Y", $date->format('U'));
    					break;
    			}
    			break;
    	}
    	
    	return $date_formated;
    }
    
} 

?>