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
 * @package    downloads
 */

/**
 * Hook class.
 */
class Hook_rss_downloads
{
    /**
     * Run function for RSS hooks.
     *
     * @param  string $_filters A list of categories we accept from
     * @param  TIME $cutoff Cutoff time, before which we do not show results from
     * @param  string $prefix Prefix that represents the template set we use
     * @set    RSS_ ATOM_
     * @param  string $date_string The standard format of date to use for the syndication type represented in the prefix
     * @param  integer $max The maximum number of entries to return, ordering by date
     * @return ?array A pair: The main syndication section, and a title (null: error)
     */
    public function run($_filters, $cutoff, $prefix, $date_string, $max)
    {
        if (!addon_installed('downloads')) {
            return null;
        }

        $filters = selectcode_to_sqlfragment($_filters, 'category_id', 'download_categories', 'parent_id', 'category_id', 'id'); // Note that the parameters are fiddled here so that category-set and record-set are the same, yet SQL is returned to deal in an entirely different record-set (entries' record-set)

        require_lang('downloads');

        if (!has_actual_page_access(get_member(), 'downloads')) {
            return null;
        }

        $content = new Tempcode();
        $_categories = $GLOBALS['SITE_DB']->query_select('download_categories', array('id', 'category'), null, '', 300);
        foreach ($_categories as $i => $_category) {
            $_categories[$i]['_title'] = get_translated_text($_category['category']);
        }
        $categories = collapse_2d_complexity('id', '_title', $_categories);
        $query = 'SELECT * FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'download_downloads WHERE add_date>' . strval($cutoff) . (((!has_privilege(get_member(), 'see_unvalidated')) && (addon_installed('unvalidated'))) ? ' AND validated=1 ' : '') . ' AND ' . $filters . ' ORDER BY add_date DESC';
        $rows = $GLOBALS['SITE_DB']->query($query, $max);
        foreach ($rows as $row) {
            $id = strval($row['id']);
            $author = $GLOBALS['FORUM_DRIVER']->get_username($row['submitter']);
            if (is_null($author)) {
                $author = '';
            }

            $news_date = date($date_string, $row['add_date']);
            $edit_date = is_null($row['edit_date']) ? '' : date($date_string, $row['edit_date']);

            $news_title = xmlentities(escape_html(get_translated_text($row['name'])));
            $_summary = get_translated_tempcode('download_downloads', $row, 'description');
            $summary = xmlentities($_summary->evaluate());
            $news = '';

            if (!array_key_exists($row['category_id'], $categories)) {
                $c = $GLOBALS['SITE_DB']->query_select_value_if_there('download_categories', 'category', array('id' => $row['category_id']));
                if (is_null($c)) {
                    continue; // Slight corruption
                }
                $categories[$row['category_id']] = get_translated_text($c);
            }
            if (!array_key_exists($row['category_id'], $categories)) {
                continue;
            }
            $category = $categories[$row['category_id']];
            $category_raw = strval($row['category_id']);

            $view_url = build_url(array('page' => 'downloads', 'type' => 'entry', 'id' => $row['id']), get_module_zone('downloads'), null, false, false, true);

            if (($prefix == 'RSS_') && (get_option('is_on_comments') == '1') && ($row['allow_comments'] >= 1)) {
                $if_comments = do_template('RSS_ENTRY_COMMENTS', array('_GUID' => '2a3615d747190e5268df1e7d9eaee7be', 'COMMENT_URL' => $view_url, 'ID' => strval($row['id'])), null, false, null, '.xml', 'xml');
            } else {
                $if_comments = new Tempcode();
            }

            $keep = symbol_tempcode('KEEP');
            $enclosure_url = find_script('dload') . '?id=' . strval($row['id']) . $keep->evaluate();
            $full_url = $row['url'];
            if (url_is_local($full_url)) {
                $full_url = get_custom_base_url() . '/' . $full_url;
            }
            list($enclosure_length,) = get_enclosure_details($row['url'], $full_url);
            $enclosure_type = 'application/octet-stream';

            $content->attach(do_template($prefix . 'ENTRY', array(
                'ENCLOSURE_URL' => $enclosure_url,
                'ENCLOSURE_LENGTH' => $enclosure_length,
                'ENCLOSURE_TYPE' => $enclosure_type,
                'VIEW_URL' => $view_url,
                'SUMMARY' => $summary,
                'EDIT_DATE' => $edit_date,
                'IF_COMMENTS' => $if_comments,
                'TITLE' => $news_title,
                'CATEGORY_RAW' => $category_raw,
                'CATEGORY' => $category,
                'AUTHOR' => $author,
                'ID' => $id,
                'NEWS' => $news,
                'DATE' => $news_date,
            ), null, false, null, '.xml', 'xml'));
        }

        require_lang('downloads');
        return array($content, do_lang('SECTION_DOWNLOADS'));
    }
}