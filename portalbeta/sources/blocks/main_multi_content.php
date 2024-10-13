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
 * Block class.
 */
class Block_main_multi_content
{
    /**
     * Find details of the block.
     *
     * @return ?array Map of block info (null: block is disabled).
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
        $info['parameters'] = array(
            'filter',
            'param',
            'efficient',
            'select',
            'select_b',
            'title',
            'zone',
            'sort',
            'days',
            'lifetime',
            'pinned',
            'no_links',
            'give_context',
            'include_breadcrumbs',
            'max',
            'start',
            'pagination',
            'root',
            'attach_to_url_filter',
            'render_if_empty',
            'guid',
            'as_guest',
        );
        return $info;
    }

    /**
     * Find caching details for the block.
     *
     * @return ?array Map of cache details (cache_on and ttl) (null: block is disabled).
     */
    public function caching_environment()
    {
        $info = array();
        $info['cache_on'] = '
            (preg_match(\'#<\w+>#\',(array_key_exists(\'filter\',$map)?$map[\'filter\']:\'\'))!=0)
            ?
            null
            :
            array(
                array_key_exists(\'as_guest\',$map)?($map[\'as_guest\']==\'1\'):false,
                array_key_exists(\'guid\',$map)?$map[\'guid\']:\'\',
                (array_key_exists(\'efficient\',$map) && $map[\'efficient\']==\'1\')?array():$GLOBALS[\'FORUM_DRIVER\']->get_members_groups(get_member(),false,true),
                array_key_exists(\'render_if_empty\',$map)?$map[\'render_if_empty\']:\'0\',
                ((array_key_exists(\'attach_to_url_filter\',$map)?$map[\'attach_to_url_filter\']:\'0\')==\'1\'),
                get_param_integer($block_id.\'_max\',array_key_exists(\'max\',$map)?intval($map[\'max\']):30),
                get_param_integer($block_id.\'_start\',array_key_exists(\'start\',$map)?intval($map[\'start\']):0),
                ((array_key_exists(\'pagination\',$map)?$map[\'pagination\']:\'0\')==\'1\'),
                ((array_key_exists(\'root\',$map)) && ($map[\'root\']!=\'\'))?intval($map[\'root\']):get_param_integer(\'keep_\'.(array_key_exists(\'param\',$map)?$map[\'param\']:\'download\').\'_root\',null),
                (array_key_exists(\'give_context\',$map)?$map[\'give_context\']:\'0\')==\'1\',
                (array_key_exists(\'include_breadcrumbs\',$map)?$map[\'include_breadcrumbs\']:\'0\')==\'1\',
                array_key_exists(\'filter\',$map)?$map[\'filter\']:\'\',
                array_key_exists(\'no_links\',$map)?$map[\'no_links\']:0,
                ((array_key_exists(\'days\',$map)) && ($map[\'days\']!=\'\'))?intval($map[\'days\']):null,
                ((array_key_exists(\'lifetime\',$map)) && ($map[\'lifetime\']!=\'\'))?intval($map[\'lifetime\']):null,
                ((array_key_exists(\'pinned\',$map)) && ($map[\'pinned\']!=\'\'))?explode(\',\',$map[\'pinned\']):array(),
                array_key_exists(\'title\',$map)?$map[\'title\']:\'\',
                array_key_exists(\'param\',$map)?$map[\'param\']:\'download\',
                array_key_exists(\'select\',$map)?$map[\'select\']:\'\',
                array_key_exists(\'select_b\',$map)?$map[\'select_b\']:\'\',
                array_key_exists(\'zone\',$map)?$map[\'zone\']:\'_SEARCH\',
                array_key_exists(\'sort\',$map)?$map[\'sort\']:\'recent\'
            )';
        $info['special_cache_flags'] = CACHE_AGAINST_DEFAULT;
        if (addon_installed('content_privacy')) {
            $info['special_cache_flags'] |= CACHE_AGAINST_MEMBER;
        }
        $info['ttl'] = (get_value('no_block_timeout') === '1') ? 60 * 60 * 24 * 365 * 5/*5 year timeout*/ : 30;
        return $info;
    }

    /**
     * Uninstall the block.
     */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('feature_lifetime_monitor');
    }

    /**
     * Install the block.
     *
     * @param  ?integer $upgrade_from What version we're upgrading from (null: new install)
     * @param  ?integer $upgrade_from_hack What hack version we're upgrading from (null: new-install/not-upgrading-from-a-hacked-version)
     */
    public function install($upgrade_from = null, $upgrade_from_hack = null)
    {
        $GLOBALS['SITE_DB']->create_table('feature_lifetime_monitor', array(
            'content_id' => '*ID_TEXT',
            'block_cache_id' => '*ID_TEXT',
            'run_period' => 'INTEGER',
            'running_now' => 'BINARY',
            'last_update' => 'TIME',
        ));
    }

    /**
     * Execute the block.
     *
     * @param  array $map A map of parameters.
     * @return Tempcode The result of execution.
     */
    public function run($map)
    {
        if (isset($map['param'])) {
            $content_type = $map['param'];
        } else {
            if (addon_installed('downloads')) {
                $content_type = 'download';
            } else {
                $hooks = find_all_hooks('systems', 'content_meta_aware');
                $content_type = key($hooks);
            }
        }

        $block_id = get_block_id($map);

        $max = get_param_integer($block_id . '_max', isset($map['max']) ? intval($map['max']) : 30);
        $start = get_param_integer($block_id . '_start', isset($map['start']) ? intval($map['start']) : 0);
        $do_pagination = ((isset($map['pagination']) ? $map['pagination'] : '0') == '1');
        $attach_to_url_filter = ((isset($map['attach_to_url_filter']) ? $map['attach_to_url_filter'] : '0') == '1');
        $root = ((isset($map['root'])) && ($map['root'] != '')) ? intval($map['root']) : get_param_integer('keep_' . $content_type . '_root', null);

        $guid = isset($map['guid']) ? $map['guid'] : '';
        $sort = isset($map['sort']) ? $map['sort'] : 'recent'; // recent|top|views|random|title or some manually typed sort order
        $select = isset($map['select']) ? $map['select'] : '';
        $select_b = isset($map['select_b']) ? $map['select_b'] : '';
        if ($select_b == '*') {
            return new Tempcode(); // Indicates some kind of referencing error, probably caused by Tempcode pre-processing - skip execution
        }
        $filter = isset($map['filter']) ? $map['filter'] : '';
        $zone = isset($map['zone']) ? $map['zone'] : '_SEARCH';
        $efficient = (isset($map['efficient']) ? $map['efficient'] : '1') == '1';
        $title = isset($map['title']) ? $map['title'] : '';
        $days = ((isset($map['days'])) && ($map['days'] != '')) ? intval($map['days']) : null;
        $lifetime = ((isset($map['lifetime'])) && ($map['lifetime'] != '')) ? intval($map['lifetime']) : null;
        $pinned = ((isset($map['pinned'])) && ($map['pinned'] != '')) ? explode(',', $map['pinned']) : array();
        $give_context = (isset($map['give_context']) ? $map['give_context'] : '0') == '1';
        $include_breadcrumbs = (isset($map['include_breadcrumbs']) ? $map['include_breadcrumbs'] : '0') == '1';

        if ((!file_exists(get_file_base() . '/sources/hooks/systems/content_meta_aware/' . filter_naughty_harsh($content_type) . '.php')) && (!file_exists(get_file_base() . '/sources_custom/hooks/systems/content_meta_aware/' . filter_naughty_harsh($content_type) . '.php'))) {
            return paragraph(do_lang_tempcode('NO_SUCH_CONTENT_TYPE', escape_html($content_type)), '', 'red_alert');
        }

        require_code('content');
        $object = get_content_object($content_type);
        $info = $object->info($zone, ($select_b == '') ? null : $select_b);
        if ($info === null) {
            return paragraph(do_lang_tempcode('IMPOSSIBLE_TYPE_USED'), '', 'red_alert');
        }

        $submit_url = $info['add_url'];
        if ($submit_url !== null) {
            $submit_url = page_link_to_url($submit_url);
        } else {
            $submit_url = '';
        }
        if (!has_actual_page_access(null, $info['cms_page'], null, null)) {
            $submit_url = '';
        }

        $first_id_field = is_array($info['id_field']) ? $info['id_field'][0] : $info['id_field'];

        // Get entries

        if (is_array($info['category_field'])) {
            $category_field_access = $info['category_field'][0];
            $category_field_select = $info['category_field'][1];
        } else {
            $category_field_access = $info['category_field'];
            $category_field_select = $info['category_field'];
        }
        if (array_key_exists('category_type', $info)) {
            if (is_array($info['category_type'])) {
                $category_type_access = $info['category_type'][0];
                $category_type_select = $info['category_type'][1];
            } else {
                $category_type_access = $info['category_type'];
                $category_type_select = $info['category_type'];
            }
        } else {
            $category_type_access = mixed();
            $category_type_select = mixed();
        }

        // Actually for categories we check access on category ID
        if ($info['is_category'] && $category_type_access !== null) {
            $category_field_access = $first_id_field;
        }

        $where = '1=1';
        $query = 'FROM ' . get_table_prefix() . $info['table'] . ' r';
        if ($info['table'] == 'catalogue_entries') {
            $where .= ' AND r.c_name NOT LIKE \'' . db_encode_like('\_%') . '\'';
        }
        if ((!$GLOBALS['FORUM_DRIVER']->is_super_admin(get_member())) && (!$efficient)) {
            $_groups = $GLOBALS['FORUM_DRIVER']->get_members_groups(get_member(), false, true);
            $groups = '';
            foreach ($_groups as $group) {
                if ($groups != '') {
                    $groups .= ' OR ';
                }
                $groups .= 'a.group_id=' . strval($group);
            }

            if ($category_field_access !== null) {
                if ($category_type_access === '<zone>') {
                    $query .= ' LEFT JOIN ' . get_table_prefix() . 'group_zone_access a ON (r.' . $category_field_access . '=a.zone_name)';
                    $query .= ' LEFT JOIN ' . get_table_prefix() . 'group_zone_access ma ON (r.' . $category_field_access . '=ma.zone_name)';
                } elseif ($category_type_access === '<page>') {
                    $query .= ' LEFT JOIN ' . get_table_prefix() . 'group_page_access a ON (r.' . $category_field_select . '=a.page_name AND r.' . $category_field_access . '=a.zone_name AND (' . $groups . '))';
                    $query .= ' LEFT JOIN ' . get_table_prefix() . 'group_zone_access a2 ON (r.' . $category_field_access . '=a2.zone_name)';
                    $query .= ' LEFT JOIN ' . get_table_prefix() . 'group_zone_access ma2 ON (r.' . $category_field_access . '=ma2.zone_name)';
                } else {
                    $query .= ' LEFT JOIN ' . get_table_prefix() . 'group_category_access a ON (' . db_string_equal_to('a.module_the_name', $category_type_access) . ' AND r.' . $category_field_access . '=a.category_name)';
                    $query .= ' LEFT JOIN ' . get_table_prefix() . 'member_category_access ma ON (' . db_string_equal_to('ma.module_the_name', $category_type_access) . ' AND r.' . $category_field_access . '=ma.category_name)';
                }
            }
            if (($category_field_select !== null) && ($category_field_select != $category_field_access) && ($info['category_type'] !== '<page>') && ($info['category_type'] !== '<zone>')) {
                $query .= ' LEFT JOIN ' . get_table_prefix() . 'group_category_access a2 ON (' . db_string_equal_to('a.module_the_name', $category_type_select) . ' AND r.' . $category_field_select . '=a2.category_name)';
                $query .= ' LEFT JOIN ' . get_table_prefix() . 'member_category_access ma2 ON (' . db_string_equal_to('ma2.module_the_name', $category_type_access) . ' AND r.' . $category_field_access . '=ma2.category_name)';
            }
            if ($category_field_access !== null) {
                $where .= ' AND ';
                if ($info['category_type'] === '<page>') {
                    $where .= '(a.group_id IS NULL) AND (' . str_replace('a.', 'a2.', $groups) . ') AND (a2.group_id IS NOT NULL)';
                    // NB: too complex to handle member-specific page permissions in this
                } else {
                    $where .= '((' . $groups . ') AND (a.group_id IS NOT NULL) OR ((ma.active_until IS NULL OR ma.active_until>' . strval(time()) . ') AND ma.member_id=' . strval(get_member()) . '))';
                }
            }
            if (($category_field_select !== null) && ($category_field_select != $category_field_access) && ($info['category_type'] !== '<page>')) {
                $where .= ' AND ';
                $where .= '((' . str_replace('a.group_id', 'a2.group_id', $groups) . ') AND (a2.group_id IS NOT NULL) OR ((ma2.active_until IS NULL OR ma2.active_until>' . strval(time()) . ') AND ma2.member_id=' . strval(get_member()) . '))';
            }
            if (array_key_exists('where', $info)) {
                $where .= ' AND ';
                $where .= $info['where'];
            }
        }

        if ((array_key_exists('validated_field', $info)) && (addon_installed('unvalidated')) && ($info['validated_field'] != '') && (has_privilege(get_member(), 'see_unvalidated'))) {
            $where .= ' AND ';
            $where .= 'r.' . $info['validated_field'] . '=1';
        }

        $x1 = '';
        $x2 = '';
        if (($select != '') && ($category_field_select !== null)) {
            $x1 = $this->build_select($select, $info, $category_field_select);
            $parent_spec__table_name = array_key_exists('parent_spec__table_name', $info) ? $info['parent_spec__table_name'] : $info['table'];
            if (($parent_spec__table_name !== null) && ($parent_spec__table_name != $info['table'])) {
                $query .= ' LEFT JOIN ' . $info['connection']->get_table_prefix() . $parent_spec__table_name . ' parent ON parent.' . $info['parent_spec__field_name'] . '=r.' . $first_id_field;
            }
        }
        if (($select_b != '') && ($category_field_access !== null)) {
            $x2 = $this->build_select($select_b, $info, $category_field_access);
        }

        if ($days !== null && $info['date_field'] !== null) {
            $where .= ' AND ';
            $where .= 'r.' . $info['date_field'] . '>=' . strval(time() - 60 * 60 * 24 * $days);
        }

        if (is_array($info['id_field'])) {
            $lifetime = null; // Cannot join on this
        }
        if ($lifetime !== null) {
            $block_cache_id = md5(serialize($map));
            $query .= ' LEFT JOIN ' . $info['connection']->get_table_prefix() . 'feature_lifetime_monitor m ON m.content_id=r.' . db_cast($first_id_field, 'CHAR') . ' AND ' . db_string_equal_to('m.block_cache_id', $block_cache_id);
            $where .= ' AND ';
            $where .= '(m.run_period IS NULL OR m.run_period<' . strval($lifetime * 60 * 60 * 24) . ')';
        }

        if (array_key_exists('extra_select_sql', $info)) {
            $extra_select_sql = $info['extra_select_sql'];
        } else {
            $extra_select_sql = '';
        }
        if (array_key_exists('extra_table_sql', $info)) {
            $query .= $info['extra_table_sql'];
        }
        if (array_key_exists('extra_where_sql', $info)) {
            $where .= ' AND ';
            $where .= $info['extra_where_sql'];
        }

        // Filtercode support
        if ($filter != '') {
            global $BLOCK_OCPRODUCTS_ERROR_EMAILS;
            $BLOCK_OCPRODUCTS_ERROR_EMAILS = true;

            // Convert the filters to SQL
            require_code('filtercode');
            list($extra_select, $extra_join, $extra_where) = filtercode_to_sql($info['connection'], parse_filtercode($filter), $content_type);
            $extra_select_sql .= implode('', $extra_select);
            $query .= implode('', $extra_join);
            $where .= $extra_where;
        }

        if (addon_installed('content_privacy')) {
            require_code('content_privacy');
            $as_guest = array_key_exists('as_guest', $map) ? ($map['as_guest'] == '1') : false;
            $viewing_member_id = $as_guest ? $GLOBALS['FORUM_DRIVER']->get_guest_id() : mixed();
            list($privacy_join, $privacy_where) = get_privacy_where_clause($content_type, 'r', $viewing_member_id);
            $query .= $privacy_join;
            $where .= $privacy_where;
        }

        // Put query together
        if ($where . $x1 . $x2 != '') {
            if ($where == '') {
                $where = '1=1';
            }
            $query .= ' WHERE ' . $where;
            if ($x1 != '') {
                $query .= ' AND (' . $x1 . ')';
            }
            if ($x2 != '') {
                $query .= ' AND (' . $x2 . ')';
            }
        }

        if ((($sort == 'average_rating') || ($sort == 'compound_rating')) && (array_key_exists('feedback_type_code', $info)) && ($info['feedback_type_code'] === null)) {
            $sort = 'title';
        }

        global $TABLE_LANG_FIELDS_CACHE;
        $lang_fields = isset($TABLE_LANG_FIELDS_CACHE[$info['table']]) ? $TABLE_LANG_FIELDS_CACHE[$info['table']] : array();
        foreach ($lang_fields as $lang_field => $lang_field_type) {
            unset($lang_fields[$lang_field]);
            $lang_fields['r.' . $lang_field] = $lang_field_type;
        }

        // Find what kind of query to run and run it
        if ($select != '-1') {
            if (substr($query, -strlen(' WHERE 1=1 AND (0=1)')) == ' WHERE 1=1 AND (0=1)') {
                // Tried to do recursive query and found nothing to query
                $max_rows = 0;
            } else {
                $max_rows = $info['connection']->query_value_if_there('SELECT COUNT(*)' . $extra_select_sql . ' ' . $query, false, true);
            }
            if ($max_rows == 0) {
                $rows = array();
            } else {
                switch ($sort) {
                    case 'random':
                    case 'fixed_random':
                    case 'fixed_random ASC':
                        $clause = db_cast('r.' . $first_id_field, 'INT');
                        $clause = '(' . db_function('MOD', array($clause, date('d'))) . ')';
                        $rows = $info['connection']->query('SELECT r.*' . $extra_select_sql . ',' . $clause . ' AS fixed_random ' . $query . ' ORDER BY fixed_random', $max, $start, false, true, $lang_fields);
                        break;
                    case 'recent_contents':
                    case 'recent_contents ASC':
                    case 'recent_contents DESC':
                        $hooks = find_all_hooks('systems', 'content_meta_aware');
                        $sort_combos = array();
                        foreach (array_keys($hooks) as $hook) {
                            $other_ob = get_content_object($hook);
                            $other_info = $other_ob->info();
                            if (($hook != $content_type) && (!is_null($other_info['parent_category_meta_aware_type'])) && ($other_info['parent_category_meta_aware_type'] == $content_type) && (is_string($other_info['parent_category_field'])) && (!is_null($other_info['add_time_field']))) {
                                $sort_combos[] = array($other_info['table'], $other_info['add_time_field'], $other_info['parent_category_field']);
                            }
                        }
                        if ($sort_combos != array()) {
                            $_order_by = array();
                            foreach ($sort_combos as $i => $sort_combo) {
                                list($other_table, $other_add_time_field, $other_category_field) = $sort_combo;
                                if ($sort == 'recent_contents DESC') {
                                    $__order_by_a = '(SELECT MAX(';
                                } else {
                                    $__order_by_a = '(SELECT MIN(';
                                }
                                $__order_by_a .= $other_add_time_field . ') FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . $other_table . ' x WHERE r.' . $first_id_field . '=x.' . $other_category_field;
                                $__order_by_a .= ')';
                                $__order_by_b = (($sort == 'recent_contents DESC') ? '0' : strval(PHP_INT_MAX)/*so empty galleries go to end of order*/);
                                $_order_by[] = db_function('COALESCE', array($__order_by_a, $__order_by_b));
                            }
                            if (count($sort_combos) == 1) {
                                $order_by = $_order_by[0];
                            } else {
                                $order_by = db_function('GREATEST', $_order_by);
                            }

                            if ($sort == 'recent_contents DESC') {
                                $order_by .= ' DESC';
                            }

                            $rows = $info['connection']->query('SELECT r.*' . $extra_select_sql . ' ' . $query . ' ORDER BY ' . $order_by, $max, $start);

                            break;
                        }
                    case 'recent':
                    case 'recent ASC':
                    case 'recent DESC':
                        if ((array_key_exists('date_field', $info)) && ($info['date_field'] !== null)) {
                            $rows = $info['connection']->query('SELECT r.*' . $extra_select_sql . ' ' . $query . ' ORDER BY r.' . $info['date_field'] . (($sort != 'recent asc') ? ' DESC' : ' ASC'), $max, $start, false, true, $lang_fields);
                            break;
                        }
                        $sort = $first_id_field;
                    case 'views':
                        if ((array_key_exists('views_field', $info)) && ($info['views_field'] !== null)) {
                            $rows = $info['connection']->query('SELECT r.*' . $extra_select_sql . ' ' . $query . ' ORDER BY r.' . $info['views_field'] . ' DESC', $max, $start, false, true, $lang_fields);
                            break;
                        }
                        $sort = $first_id_field;
                    case 'average_rating':
                    case 'average_rating ASC':
                    case 'average_rating DESC':
                        if ((array_key_exists('feedback_type_code', $info)) && ($info['feedback_type_code'] !== null)) {
                            if ($sort == 'average_rating') {
                                $sort .= ' DESC';
                            }

                            $select_rating = ',(SELECT AVG(rating) FROM ' . get_table_prefix() . 'rating WHERE ' . db_string_equal_to('rating_for_type', $info['feedback_type_code']) . ' AND rating_for_id=' . db_cast($first_id_field, 'CHAR') . ') AS average_rating';
                            $rows = $info['connection']->query('SELECT r.*' . $extra_select_sql . $select_rating . ' ' . $query . ' ORDER BY ' . $sort, $max, $start, false, true, $lang_fields);
                            break;
                        }
                        $sort = $first_id_field;
                    case 'compound_rating':
                    case 'compound_rating ASC':
                    case 'compound_rating DESC':
                        if ((array_key_exists('feedback_type_code', $info)) && ($info['feedback_type_code'] !== null)) {
                            if ($sort == 'compound_rating') {
                                $sort .= ' DESC';
                            }

                            $select_rating = ',(SELECT SUM(rating-1) FROM ' . get_table_prefix() . 'rating WHERE ' . db_string_equal_to('rating_for_type', $info['feedback_type_code']) . ' AND rating_for_id=' . db_cast($first_id_field, 'CHAR') . ') AS compound_rating';
                            $rows = $info['connection']->query('SELECT r.*' . $extra_select_sql . $select_rating . ' ' . $query . ' ORDER BY ' . $sort, $max, $start, false, true, $lang_fields);
                            break;
                        }
                        $sort = $first_id_field;
                    case 'title':
                    case 'title ASC':
                    case 'title DESC':
                        if (strpos($sort, ' ') === false) {
                            $sort .= ' ASC';
                        }
                        $sort_order = preg_replace('#^.* #', '', $sort);

                        $sql = 'SELECT r.*' . $extra_select_sql . ' ' . $query . ' ORDER BY ';
                        if ((array_key_exists('title_field', $info)) && (strpos($info['title_field'], ':') === false)) {
                            if ($info['title_field_dereference']) {
                                $sql .= $GLOBALS['SITE_DB']->translate_field_ref($info['title_field']) . ' ' . $sort_order;
                            } else {
                                $sql .= 'r.' . $info['title_field'] . ' ' . $sort_order;
                            }
                        } else {
                            if (isset($info['order_field'])) {
                                $sql .= 'r.' . $info['order_field'] . ' ' . $sort_order . ',';
                            } else {
                                $sql .= 'r.' . $first_id_field . ' ' . $sort_order;
                            }
                        }
                        $rows = $info['connection']->query($sql, $max, $start, false, true, $lang_fields);
                        break;
                    default: // Some manual order
                        $rows = $info['connection']->query('SELECT r.*' . $extra_select_sql . ' ' . $query . ' ORDER BY ' . $sort, $max, $start, false, true, $lang_fields);
                        break;
                }
            }
        } else {
            $max_rows = 0;
            $rows = array();
        }

        $pinned_order = array();

        require_code('content');

        // Add in requested pinned awards
        if (($pinned != array()) && (addon_installed('awards'))) {
            if (db_has_subqueries($GLOBALS['SITE_DB']->connection_read)) {
                $where = '';
                foreach ($pinned as $p) {
                    if (trim($p) == '') {
                        continue;
                    }
                    if ($where != '') {
                        $where .= ' OR ';
                    }
                    $where .= 'a_type_id=' . strval(intval($p));
                }
                if ($where == '') {
                    $awarded_content_ids = array();
                } else {
                    $award_sql = 'SELECT a.a_type_id,a.content_id FROM ' . get_table_prefix() . 'award_archive a JOIN (SELECT MAX(date_and_time) AS max_date,a_type_id FROM ' . get_table_prefix() . 'award_archive WHERE ' . $where . ' GROUP BY a_type_id) b ON b.a_type_id=a.a_type_id AND a.date_and_time=b.max_date WHERE ' . str_replace('a_type_id', 'a.a_type_id', $where);
                    $awarded_content_ids = collapse_2d_complexity('a_type_id', 'content_id', $GLOBALS['SITE_DB']->query($award_sql, null, null, false, true));
                }
            } else {
                $awarded_content_ids = array();
                foreach ($pinned as $p) {
                    if (trim($p) == '') {
                        continue;
                    }
                    $where = 'a_type_id=' . strval(intval($p));
                    $awarded_content_ids += collapse_2d_complexity('a_type_id', 'content_id', $GLOBALS['SITE_DB']->query('SELECT a_type_id,content_id FROM ' . get_table_prefix() . 'award_archive WHERE ' . $where . ' ORDER BY date_and_time ASC', 1, null, false, true));
                }
            }

            foreach ($pinned as $p) {
                if (!isset($awarded_content_ids[intval($p)])) {
                    continue;
                }
                $awarded_content_id = $awarded_content_ids[intval($p)];

                $award_content_row = content_get_row($awarded_content_id, $info);

                if (($award_content_row !== null) && ((!addon_installed('unvalidated')) || (!isset($info['validated_field'])) || ($award_content_row[$info['validated_field']] != 0))) {
                    $pinned_order[] = $award_content_row;
                }
            }
        }

        if (count($pinned_order) > 0) { // Re-sort with pinned awards if appropriate
            if (count($rows) > 0) {
                $old_rows = $rows;
                $rows = array();
                $total_count = count($old_rows) + count($pinned_order);
                $used_ids = array();

                // Carry on as it should be
                for ($t_count = 0; $t_count < $total_count; $t_count++) {
                    if (array_key_exists($t_count, $pinned_order)) { // Pinned ones go first, so order # for them is in sequence with main loop order
                        $str_id = extract_content_str_id_from_data($pinned_order[$t_count], $info);
                        if (!in_array($str_id, $used_ids)) {
                            $rows[] = $pinned_order[$t_count];
                            $used_ids[] = $str_id;
                        }
                    } else {
                        $temp_row = $old_rows[$t_count - count($pinned_order)];
                        $str_id = extract_content_str_id_from_data($temp_row, $info);
                        if (!in_array($str_id, $used_ids)) {
                            $rows[] = $temp_row;
                            $used_ids[] = $str_id;
                        }
                    }
                }
            } else {
                switch ($sort) {
                    case 'recent':
                        if (array_key_exists('date_field', $info)) {
                            sort_maps_by($pinned_order, $info['date_field']);
                            $rows = array_reverse($pinned_order);
                        }
                        break;
                    case 'views':
                        if (array_key_exists('views_field', $info)) {
                            sort_maps_by($pinned_order, $info['views_field']);
                            $rows = array_reverse($pinned_order);
                        }
                        break;
                }
            }
        }

        // Sort out run periods
        if ($lifetime !== null) {
            $lifetime_monitor = list_to_map('content_id', $GLOBALS['SITE_DB']->query_select('feature_lifetime_monitor', array('content_id', 'run_period', 'last_update'), array('block_cache_id' => $block_cache_id, 'running_now' => 1)));
        }

        // Move towards render...

        if ($info['archive_url'] !== null) {
            $archive_url = page_link_to_tempcode_url($info['archive_url']);
        } else {
            $archive_url = new Tempcode();
        }
        $view_url = array_key_exists('view_url', $info) ? $info['view_url'] : new Tempcode();

        $done_already = array(); // We need to keep track, in case those pulled up via awards would also come up naturally

        $rendered_content = array();
        $content_data = array();
        foreach ($rows as $row) {
            if (count($done_already) == $max) {
                break;
            }

            // Get content ID
            $content_id = extract_content_str_id_from_data($row, $info);

            // De-dupe
            if (array_key_exists($content_id, $done_already)) {
                continue;
            }
            $done_already[$content_id] = 1;

            // Lifetime managing
            if ($lifetime !== null) {
                if (!array_key_exists($content_id, $lifetime_monitor)) {
                    // Test to see if it is actually there in the past - we only loaded the "running now" ones for performance reasons. Any new ones coming will trigger extra queries to see if they've been used before, as a tradeoff to loading potentially 10's of thousands of rows.
                    $lifetime_monitor += list_to_map('content_id', $GLOBALS['SITE_DB']->query_select('feature_lifetime_monitor', array('content_id', 'run_period', 'last_update'), array('block_cache_id' => $block_cache_id, 'content_id' => $content_id)));
                }

                if (array_key_exists($content_id, $lifetime_monitor)) {
                    $GLOBALS['SITE_DB']->query_update('feature_lifetime_monitor', array(
                        'run_period' => $lifetime_monitor[$content_id]['run_period'] + (time() - $lifetime_monitor[$content_id]['last_update']),
                        'running_now' => 1,
                        'last_update' => time(),
                    ), array('content_id' => $content_id, 'block_cache_id' => $block_cache_id));
                    unset($lifetime_monitor[$content_id]);
                } else {
                    $GLOBALS['SITE_DB']->query_insert('feature_lifetime_monitor', array(
                        'content_id' => $content_id,
                        'block_cache_id' => $block_cache_id,
                        'run_period' => 0,
                        'running_now' => 1,
                        'last_update' => time(),
                    ));
                }
            }

            // Render
            $rendered_content[] = $object->run($row, $zone, $give_context, $include_breadcrumbs, $root, $attach_to_url_filter, $guid);

            // Try and get a better submit url
            $submit_url = str_replace('%21', $content_id, $submit_url);

            $content_data[] = array('URL' => str_replace('%21', $content_id, $view_url->evaluate()));
        }

        // Sort out run periods of stuff gone
        if ($lifetime !== null) {
            foreach (array_keys($lifetime_monitor) as $content_id) { // Any remaining have not been pulled up
                if (is_integer($content_id)) {
                    $content_id = strval($content_id);
                }

                $GLOBALS['SITE_DB']->query_update('feature_lifetime_monitor', array(
                    'run_period' => $lifetime_monitor[$content_id]['run_period'] + (time() - $lifetime_monitor[$content_id]['last_update']),
                    'running_now' => 0,
                    'last_update' => time(),
                ), array('content_id' => $content_id, 'block_cache_id' => $block_cache_id));
            }
        }

        if ((isset($map['no_links'])) && ($map['no_links'] == '1')) {
            $submit_url = new Tempcode();
            $archive_url = new Tempcode();
        }

        // Empty? Bomb out somehow
        if (count($rendered_content) == 0) {
            if ((isset($map['render_if_empty'])) && ($map['render_if_empty'] == '0')) {
                return new Tempcode();
            }
        }

        // Pagination
        $pagination = mixed();
        if ($do_pagination) {
            require_code('templates_pagination');
            $pagination = pagination(do_lang_tempcode($info['content_type_label']), $start, $block_id . '_start', $max, $block_id . '_max', $max_rows);
        }

        return do_template('BLOCK_MAIN_MULTI_CONTENT', array(
            '_GUID' => ($guid != '') ? $guid : '9035934bc9b25f57eb8d23bf100b5796',
            'BLOCK_PARAMS' => block_params_arr_to_str(array('block_id' => $block_id) + $map),
            'TYPE' => do_lang_tempcode($info['content_type_label']),
            'TITLE' => $title,
            'CONTENT' => $rendered_content,
            'CONTENT_TYPE' => $content_type,
            'CONTENT_DATA' => $content_data,
            'SUBMIT_URL' => $submit_url,
            'ARCHIVE_URL' => $archive_url,
            'PAGINATION' => $pagination,
            'ADD_STRING' => content_language_string($content_type, 'ADD'),

            'START' => strval($start),
            'MAX' => strval($max),
            'START_PARAM' => $block_id . '_start',
            'MAX_PARAM' => $block_id . '_max',
            'EXTRA_GET_PARAMS' => (get_param_integer($block_id . '_max', null) === null) ? null : ('&' . $block_id . '_max=' . urlencode(strval($max))),
        ));
    }

    /**
     * Make a select SQL fragment.
     *
     * @param  string $select The select string.
     * @param  array $info Map of details of our content type.
     * @param  string $category_field_select The field name of the category to select against.
     * @return string SQL fragment.
     */
    public function build_select($select, $info, $category_field_select)
    {
        $parent_spec__table_name = array_key_exists('parent_spec__table_name', $info) ? $info['parent_spec__table_name'] : $info['table'];
        $parent_field_name = $info['is_category'] ? (is_array($info['id_field']) ? implode(',', $info['id_field']) : $info['id_field']) : $category_field_select;
        if ($parent_field_name === null) {
            $parent_spec__table_name = null;
        }
        $parent_spec__parent_name = array_key_exists('parent_spec__parent_name', $info) ? $info['parent_spec__parent_name'] : null;
        $parent_spec__field_name = array_key_exists('parent_spec__field_name', $info) ? $info['parent_spec__field_name'] : null;
        $id_field_numeric = ((!array_key_exists('id_field_numeric', $info)) || ($info['id_field_numeric']));
        $category_is_string = ((array_key_exists('category_is_string', $info)) && (is_array($info['category_is_string']) ? $info['category_is_string'][1] : $info['category_is_string']));

        require_code('selectcode');

        $sql = selectcode_to_sqlfragment($select, 'r.' . (is_array($info['id_field']) ? implode(',', $info['id_field']) : $info['id_field']), $parent_spec__table_name, $parent_spec__parent_name, 'r.' . $parent_field_name, $parent_spec__field_name, $id_field_numeric, !$category_is_string);
        return $sql;
    }
}