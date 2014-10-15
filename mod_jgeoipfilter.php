<?php
/**
 * @package     jGeoIPFilter Module
 * @version     3.0.0
 * @@author     Valeri Markov <val@siteground.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

require_once(JPATH_SITE.'/modules/mod_jgeoipfilter/Reader.php');

use MaxMind\Db\Reader;

/* Determine the IP address of the visitor */
$ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = false;
        
/* Try to fetch the country by IP, only if IP has been detected */
if($ipaddress !== FALSE)
{
    $reader = new Reader(JPATH_SITE.'/modules/mod_jgeoipfilter/GeoLite2-Country.mmdb');
    $country = $reader->get($ipaddress);
    $reader->close();
    
    /* Check if the current visitor country is in the country filter list, as defined in administrator area */
    if(array_search($country['country']['iso_code'], $params->get('country_list',array())) !== FALSE)
    {
        // It is a filtered country. Try to determine what action is to be taken.
        $action = $params->get('action_type',0);
        if($action == 0)
        {
            // Show 404
            JError::raiseError(404, "Page Not Found");
        } 
        elseif ($action == 1)
        {
            // Show 403
            JError::raiseError(403, "Forbidden");
        }
        elseif ($action == 2)
        {
            // Redirect to URL.
            // Get the current URL and compare it to the target URL, as we do not wish to create infinite redirect loop.
            $pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
            if ($_SERVER["SERVER_PORT"] != "80")
            {
                $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
            } 
            else 
            {
                $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
            }
            $target_url = $params->get('redirect_url',false);
            
            //Remove trailing slash, if present, from both URLs
            if(substr($target_url, -1) == '/') {
                $target_url = substr($target_url, 0, -1);
            }
            if(substr($pageURL, -1) == '/') {
                $pageURL = substr($pageURL, 0, -1);
            }
            
            // Case insensitive comparison.
            if($target_url !== FALSE AND strtolower($target_url) != strtolower($pageURL))
            {
                header('Location: '.$target_url); //@TODO: check if headers were not already sent
            }
        } // Action if end.
    } // IP-2-Country if end.
} // IP address if end.

// End of file: modules/mod_jgeoipfilter/mod_jgeoipfilter.php