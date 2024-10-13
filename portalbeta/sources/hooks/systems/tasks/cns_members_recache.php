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
 * @package    core_cns
 */

/**
 * Hook class.
 */
class Hook_task_cns_members_recache
{
    /**
     * Run the task hook.
     *
     * @return ?array A tuple of at least 2: Return mime-type, content (either Tempcode, or a string, or a filename and file-path pair to a temporary file), map of HTTP headers if transferring immediately, map of ini_set commands if transferring immediately (null: show standard success message)
     */
    public function run()
    {
        cns_require_all_forum_stuff();

        require_code('cns_posts_action');
        require_code('cns_posts_action2');

        // Members
        $start = 0;
        do {
            $members = $GLOBALS['FORUM_DB']->query_select('f_members', array('id'), null, '', 500, $start);
            foreach ($members as $member) {
                cns_force_update_member_post_count($member['id']);
                $num_warnings = $GLOBALS['FORUM_DB']->query_select_value('f_warnings', 'COUNT(*)', array('w_member_id' => $member['id'], 'w_is_warning' => 1));
                $GLOBALS['FORUM_DB']->query_update('f_members', array('m_cache_warnings' => $num_warnings), array('id' => $member['id']), '', 1);
            }
            $start += 500;
        } while (array_key_exists(0, $members));

        return null;
    }
}