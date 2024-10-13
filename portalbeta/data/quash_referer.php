<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Find the active domain name.
 *
 * @return ID_TEXT Active domain name
 */
function qr_get_domain()
{
    if (!empty($_SERVER['HTTP_HOST'])) {
        return $_SERVER['HTTP_HOST'];
    }
    if (!empty($_ENV['HTTP_HOST'])) {
        return $_ENV['HTTP_HOST'];
    }
    if (function_exists('gethostname')) {
        return gethostname();
    }
    if (!empty($_SERVER['SERVER_ADDR'])) {
        return $_SERVER['SERVER_ADDR'];
    }
    if (!empty($_ENV['SERVER_ADDR'])) {
        return $_ENV['SERVER_ADDR'];
    }
    if (!empty($_SERVER['LOCAL_ADDR'])) {
        return $_SERVER['LOCAL_ADDR'];
    }
    if (!empty($_ENV['LOCAL_ADDR'])) {
        return $_ENV['LOCAL_ADDR'];
    }
    return 'localhost';
}

$target = $_GET['url'];
if (get_magic_quotes_gpc()) {
    $target = stripslashes($target);
}
$target = str_replace(array("\r", "\n"), array('', ''), $target);

// Only allows redirections from our own server
$domain = qr_get_domain();
$OUR_SERVER = 'http://' . $domain;
if (substr($_SERVER['HTTP_REFERER'], 0, strlen($OUR_SERVER)) == $OUR_SERVER) {
    header('Location: ' . $target);
}
