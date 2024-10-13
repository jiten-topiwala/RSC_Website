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
class Hook_realtime_rain_cns
{
    /**
     * Run function for realtime-rain hooks.
     *
     * @param  TIME $from Start of time range.
     * @param  TIME $to End of time range.
     * @return array A list of template parameter sets for rendering a 'drop'.
     */
    public function run($from, $to)
    {
        $drops = array();
        if (get_forum_type() == 'cns') {
            // Member's joining (f_members table)
            if (has_actual_page_access(get_member(), 'members')) {
                $rows = $GLOBALS['FORUM_DB']->query('SELECT m_ip_address,id AS member_id,m_join_time AS timestamp FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_members WHERE m_join_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

                foreach ($rows as $row) {
                    $timestamp = $row['timestamp'];
                    $member_id = $row['member_id'];

                    if (is_guest($member_id)) {
                        continue;
                    }

                    $drops[] = rain_get_special_icons($row['m_ip_address'], $timestamp) + array(
                            'TYPE' => 'join',
                            'FROM_MEMBER_ID' => strval($member_id),
                            'TO_MEMBER_ID' => null,
                            'TITLE' => do_lang('JOINED') . ': ' . $GLOBALS['FORUM_DRIVER']->get_username($member_id),
                            'IMAGE' => $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id),
                            'TIMESTAMP' => strval($timestamp),
                            'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                            'TICKER_TEXT' => null,
                            'URL' => $GLOBALS['FORUM_DRIVER']->member_profile_url($member_id, false, true),
                            'IS_POSITIVE' => true,
                            'IS_NEGATIVE' => false,

                            // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                            'FROM_ID' => 'member_' . strval($member_id),
                            'TO_ID' => null,
                            'GROUP_ID' => null,
                        );
                }
            }

            // Forum posts (f_posts table)
            if ((has_actual_page_access(get_member(), 'topicview')) && (addon_installed('cns_forum'))) {
                $rows = $GLOBALS['FORUM_DB']->query('SELECT p_intended_solely_for,id,p_poster AS member_id,p_time AS timestamp,p_cache_forum_id,p_post,p_title,p_ip_address FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_posts WHERE p_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

                foreach ($rows as $row) {
                    if (is_null($row['p_cache_forum_id'])) {
                        continue;
                    }
                    if (!is_null($row['p_intended_solely_for'])) {
                        continue;
                    }
                    if (!has_category_access(get_member(), 'forums', strval($row['p_cache_forum_id']))) {
                        continue;
                    }

                    $timestamp = $row['timestamp'];
                    $member_id = $row['member_id'];

                    $ticker_text = strip_comcode(get_translated_text($row['p_post'], $GLOBALS['FORUM_DB']));

                    $drops[] = rain_get_special_icons($row['p_ip_address'], $timestamp, null, $ticker_text) + array(
                            'TYPE' => 'post',
                            'FROM_MEMBER_ID' => strval($member_id),
                            'TO_MEMBER_ID' => null,
                            'TITLE' => ($row['p_title'] == '') ? rain_truncate_for_title(strip_comcode(get_translated_text($row['p_post'], $GLOBALS['FORUM_DB']))) : $row['p_title'],
                            'IMAGE' => is_guest($member_id) ? rain_get_country_image($row['p_ip_address']) : $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id),
                            'TIMESTAMP' => strval($timestamp),
                            'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                            'TICKER_TEXT' => $ticker_text,
                            'URL' => $GLOBALS['FORUM_DRIVER']->post_url($row['id'], $row['p_cache_forum_id'], true),
                            'IS_POSITIVE' => false,
                            'IS_NEGATIVE' => false,

                            // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                            'FROM_ID' => 'member_' . strval($member_id),
                            'TO_ID' => null,
                            'GROUP_ID' => 'post_' . strval($row['id']),
                        );
                }
            }
        }

        return $drops;
    }
}