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
 * @package    cns_forum
 */

/**
 * Hook class.
 */
class Hook_unvalidated_cns_topics
{
    /**
     * Find details on the unvalidated hook.
     *
     * @return ?array Map of hook info (null: hook is disabled).
     */
    public function info()
    {
        if (get_forum_type() != 'cns') {
            return null;
        }

        require_lang('cns');

        $info = array();
        $info['db_table'] = 'f_topics';
        $info['db_identifier'] = 'id';
        $info['db_validated'] = 't_validated';
        $info['db_title'] = 't_cache_first_title';
        $info['db_title_dereference'] = false;
        $info['db_add_date'] = 't_cache_first_time';
        $info['db_edit_date'] = 't_cache_last_time';
        $info['edit_module'] = 'topics';
        $info['edit_type'] = 'edit_topic';
        $info['edit_identifier'] = 'id';
        $info['title'] = do_lang_tempcode('FORUM_TOPICS');
        $info['is_minor'] = true;
        $info['db'] = $GLOBALS['FORUM_DB'];

        return $info;
    }
}