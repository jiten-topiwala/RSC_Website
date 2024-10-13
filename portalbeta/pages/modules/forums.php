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
 * Module page class.
 */
class Module_forums
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        // Omitted due to being Conversr
        if ((get_forum_type() == 'cns') || (get_forum_type() == 'none')) {
            return null;
        }

        return array(
            '!' => array('SECTION_FORUMS', 'menu/social/forum/forums'),
        );
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        $base_url = get_forum_base_url();

        $forums = get_param_string('url', $base_url . '/');

        if (substr($forums, 0, strlen($base_url)) != $base_url) {
            $GLOBALS['OUTPUT_STREAMING'] = false; // Too complex to do a pre_run for this properly
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        $base_url = get_forum_base_url();

        $access_url = get_param_string('url', $base_url . '/');

        foreach ($_GET as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $_key => $_val) { // We'll only support one level deep.
                    if (get_magic_quotes_gpc()) {
                        $_val = stripslashes($_val);
                    }

                    if (strpos($access_url, '?') === false) {
                        $access_url .= '?';
                    } else {
                        $access_url .= '&';
                    }
                    $access_url .= $key . '[' . $_key . ']' . '=' . urlencode($_val);
                }
            } else {
                if (get_magic_quotes_gpc()) {
                    $val = stripslashes($val);
                }

                if (strpos($access_url, '?') === false) {
                    $access_url .= '?';
                } else {
                    $access_url .= '&';
                }
                $access_url .= $key . '=' . urlencode($val);
            }
        }

        if (substr($access_url, 0, strlen($base_url)) != $base_url) {
            $base_url = rtrim($access_url, '/');
            if ((strpos($base_url, '.php') !== false) || (strpos($base_url, '?') !== false)) {
                $base_url = dirname($base_url);
            }

            //log_hack_attack_and_exit('REFERRER_IFRAME_HACK'); No longer a hack attack becase people webmasters changed their forum base URL at some point, creating problems with old bookmarks!
            require_code('site2');
            smart_redirect(get_self_url(true, false, array('url' => get_forum_base_url())));
        }

        $old_method = false;
        if ($old_method) {
            return do_template('FORUMS_EMBED', array('_GUID' => '159575f6b83c5366d29e184a8dd5fc49', 'FORUMS' => $access_url));
        }

        $GLOBALS['SCREEN_TEMPLATE_CALLED'] = '';

        require_code('integrator');
        $result = reprocess_url($access_url, $base_url);

        return do_template('COMCODE_SURROUND', array('_GUID' => '4d5a8ce37df94f7d61f1a96f5689b9c0', 'CLASS' => 'float_surrounder', 'CONTENT' => protect_from_escaping($result)));
    }
}