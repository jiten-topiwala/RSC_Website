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
 * @package    chat
 */

/**
 * Hook class.
 */
class Hook_realtime_rain_chat
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

        if (has_actual_page_access(get_member(), 'chat')) {
            require_code('chat');

            $rows = $GLOBALS['SITE_DB']->query('SELECT ip_address,the_message,date_and_time AS timestamp,room_id,member_id FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'chat_messages WHERE system_message=0 AND date_and_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

            foreach ($rows as $row) {
                if (!check_chatroom_access($row['room_id'], true)) {
                    continue;
                }

                $timestamp = $row['timestamp'];
                $member_id = $row['member_id'];

                $message = get_translated_text($row['the_message']);
                if (strpos($message, '[private') !== false) {
                    continue;
                }
                $message = strip_comcode($message);
                if ($message == '') {
                    continue;
                }

                $drops[] = rain_get_special_icons($row['ip_address'], $timestamp, null, $message) + array(
                        'TYPE' => 'chat',
                        'FROM_MEMBER_ID' => strval($member_id),
                        'TO_MEMBER_ID' => null,
                        'TITLE' => rain_truncate_for_title($message),
                        'IMAGE' => is_guest($member_id) ? rain_get_country_image($row['ip_address']) : $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id),
                        'TIMESTAMP' => strval($timestamp),
                        'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                        'TICKER_TEXT' => $message,
                        'URL' => build_url(array('page' => 'points', 'type' => 'member', 'id' => $member_id), '_SEARCH'),
                        'IS_POSITIVE' => false,
                        'IS_NEGATIVE' => false,

                        // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                        'FROM_ID' => 'member_' . strval($member_id),
                        'TO_ID' => null,
                        'GROUP_ID' => 'room_' . strval($row['room_id']),
                    );
            }
        }

        return $drops;
    }
}