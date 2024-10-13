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
 * @package    actionlog
 */

/**
 * Hook class.
 */
class Hook_realtime_rain_actionlog
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

        $rows = $GLOBALS['SITE_DB']->query('SELECT ip,the_type,member_id,date_and_time AS timestamp FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'actionlogs WHERE date_and_time BETWEEN ' . strval($from) . ' AND ' . strval($to));

        if (has_actual_page_access(get_member(), 'admin_actionlog')) {
            require_all_lang();

            foreach ($rows as $row) {
                // Events considered elsewhere anyway
                if ($row['the_type'] == 'ADD_NEWS') {
                    continue;
                }
                if ($row['the_type'] == 'CHOOSE_POLL') {
                    continue;
                }

                $timestamp = $row['timestamp'];
                $member_id = $row['member_id'];

                $drops[] = rain_get_special_icons($row['ip'], $timestamp) + array(
                        'TYPE' => 'actionlog',
                        'FROM_MEMBER_ID' => strval($member_id),
                        'TO_MEMBER_ID' => null,
                        'TITLE' => do_lang($row['the_type']),
                        'IMAGE' => is_guest($member_id) ? rain_get_country_image($row['ip']) : $GLOBALS['FORUM_DRIVER']->get_member_avatar_url($member_id),
                        'TIMESTAMP' => strval($timestamp),
                        'RELATIVE_TIMESTAMP' => strval($timestamp - $from),
                        'TICKER_TEXT' => null,
                        'URL' => null,
                        'IS_POSITIVE' => false,
                        'IS_NEGATIVE' => false,

                        // These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
                        'FROM_ID' => 'member_' . strval($member_id),
                        'TO_ID' => null,
                        'GROUP_ID' => null,
                    );
            }
        }

        return $drops;
    }
}