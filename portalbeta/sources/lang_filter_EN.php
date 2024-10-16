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

/*
For other language packs you can copy this file to the obvious new name. This is optional, providing code-based improvements to a pack.
*/

/**
 * Do filtering for the bundled English language pack.
 *
 * @package        core
 */
class LangFilter_EN extends LangFilter
{
    private $vowels;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->vowels = array('a' => true, 'e' => true, 'i' => true, 'o' => true, 'u' => true);
    }

    /**
     * Do a compile-time filter.
     *
     * @param  ?string $key Language string ID (null: not a language string)
     * @param  string $value String value
     * @param  ?LANGUAGE_NAME $lang Language (null: current language)
     * @return string The suffix
     */
    public function compile_time($key, $value, $lang = null)
    {
        if ($lang === null) {
            $lang = user_lang();
        }

        // Broken into sets. We don't need to include "d"/"s"/"r" suffixes because the base word is a stem of that. But "ing" suffixes mean removing a letter so are needed. Some completely standard long stem transfers are done as universal replaces elsewhere.
        // All words are stem bound, but not tail bound.
        static $make_uncle_sam_happy = null;
        static $the_sun_never_sets_on_the_british_empire = null;
        if ($make_uncle_sam_happy === null) {
            $make_uncle_sam_happy = array(
                // Spelling...

                'analyse' => 'analyze',
                'analysing' => 'analyzing',

                'apologise' => 'apologize',
                'apologising' => 'apologizing',

                'artefact' => 'artifact',

                'authorise' => 'authorize',
                'authorising' => 'authorizing',

                'behaviour' => 'behavior',

                'cancelled' => 'canceled',
                'cancelling' => 'canceling',

                'catalogue' => 'catalog',

                'categorise' => 'categorize',
                'categorising' => 'categorizing',

                'centralise' => 'centralize',
                'centralising' => 'centralizing',

                'centre' => 'center',
                'centring' => 'centering',

                'colour' => 'color',

                'criticise' => 'criticize',
                'criticising' => 'criticizing',

                'customise' => 'customize',
                'customising' => 'customizing',

                'defence' => 'defense',

                'dialogue' => 'dialog',

                'emphasise' => 'emphasize',
                'emphasising' => 'emphasizing',

                'encyclopaedic' => 'encyclopedic',

                'favour' => 'favor',
                'favouring' => 'favoring',

                'finalise' => 'finalise',
                'finalising' => 'finalising',

                'fulfil' => 'fulfill',

                'immunise' => 'immunize',
                'immunising' => 'immunizing',

                'initialise' => 'initialize',
                'initialising' => 'initializing',

                'italicise' => 'italicize',
                'italicising' => 'italicizing',

                'labelled' => 'labeled',
                'labelling' => 'labeling',

                'licence' => 'license',
                'licencing' => 'licensing',

                'maximise' => 'maximize',
                'maximising' => 'maximizing',

                'minimise' => 'minimize',
                'minimising' => 'minimizing',

                'misbehaviour' => 'misbehavior',

                'neighbour' => 'neighbor',

                'offence' => 'offense',

                'optimise' => 'optimize',
                'optimising' => 'optimizing',

                'organise' => 'organize',
                'organising' => 'organizing',

                'penalise' => 'penalize',
                'penalising' => 'penalizing',

                'personalise' => 'personalize',
                'personalising' => 'personalizing',

                'prioritise' => 'prioritize',
                'prioritising' => 'prioritizing',

                'randomise' => 'randomize',
                'randomising' => 'randomizing',

                'realise' => 'realize',
                'realising' => 'realizing',

                'recognise' => 'recognise',
                'recognising' => 'recognizing',

                'standardise' => 'standardize',
                'standardising' => 'standardizing',

                'summarise' => 'summarize',
                'summarising' => 'summarizing',

                'symbolise' => 'symbolize',
                'symbolising' => 'symbolizing',

                'synchronise' => 'synchronize',
                'synchronising' => 'synchronizing',

                'theatre' => 'theater',

                'unauthorised' => 'unauthorized',

                'unrecognised' => 'unrecognized',

                'utilise' => 'utilize',
                'utilising' => 'utilizing',

                'victimise' => 'victimize',
                'victimising' => 'victimizing',

                'visualise' => 'visualize',
                'visualising' => 'visualizing',

                // Various...

                'forename (first name)' => 'first name',
                'surname (last name)' => 'last name',
                'forename' => 'first name',
                'surname' => 'last name',
                'maths' => 'math',
                'tick (check)' => 'check',
                'untick (uncheck)' => 'uncheck',
                'ticked (checked)' => 'checked',
                'unticked (unchecked)' => 'unchecked',
                'ticking (checking)' => 'checking',
                'unticking (unchecking)' => 'unchecking',
                //'bill' => 'invoice', not needed and likely to be substring
            );

            $the_sun_never_sets_on_the_british_empire = array( // Tally ho
                'tick (check)' => 'tick',
                'untick (uncheck)' => 'untick',
                'ticked (checked)' => 'ticked',
                'unticked (unchecked)' => 'unticked',
                'ticking (checking)' => 'ticking',
                'unticking (unchecking)' => 'unticking',
            ); // pip pip

            $remapping = array();

            // Put in correct brand name
            if (!is_null($key)) {
                $remapping['the software'] = preg_quote(brand_name(), '#');
                $remapping['the website software'] = preg_quote(brand_name(), '#');
                $remapping['other webmasters'] = 'other ' . preg_quote(brand_name(), '#') . ' users';
            }

            // Fix bad contextualisation
            $remapping['on Yesterday'] = 'Yesterday';
            $remapping['on Today'] = 'Today';

            $the_sun_never_sets_on_the_british_empire += $remapping;
            $make_uncle_sam_happy += $remapping;
        }

        // American <> British
        static $_is_american = null;
        if ($_is_american === null) {
            if (function_exists('get_option')) {
                $_is_american = (get_option('yeehaw') == '1') || ($lang == 'EN_US');
                $is_american = $_is_american;
            } else {
                $is_american = true;
            }
        } else {
            $is_american = $_is_american;
        }
        if ($is_american) {
            // NB: Below you will see there are exceptions, typically when the base word already naturally ends with "se" on the end, it uses "s" not "z"

            $value = str_replace('sation', 'zation', $value);
            $value = str_replace('converzation', 'conversation', $value); // Exception, put this back
            $value = str_replace('Converzation', 'Conversation', $value); // Exception, put this back

            $value = str_replace('sable', 'zable', $value);
            $value = str_replace('dizable', 'disable', $value); // Exception, put this back
            $value = str_replace('Dizable', 'Disable', $value); // Exception, put this back
            $value = str_replace('advizable', 'advisable', $value); // Exception, put this back
            $value = str_replace('Advizable', 'Advisable', $value); // Exception, put this back
            $value = str_replace('purchazable', 'purchasable', $value); // Exception, put this back
            $value = str_replace('Purchazable', 'Purchasable', $value); // Exception, put this back
            $value = str_replace('uzable', 'usable', $value); // Exception, put this back

            $value = str_replace('sational', 'zational', $value);
            $value = str_replace('senzational', 'sensational', $value); // Exception, put this back

            $remapping = $make_uncle_sam_happy;
        } else {
            $remapping = $the_sun_never_sets_on_the_british_empire;
        }

        foreach ($remapping as $authentic => $perverted) {
            if (stripos($value, $authentic) !== false) {
                $value = preg_replace(
                    '#(^|\s)' . preg_quote($authentic, '#') . '#',
                    '$1' . $perverted,
                    $value
                );
                $value = preg_replace(
                    '#(^|\s)' . preg_quote(ucfirst($authentic), '#') . '#',
                    '$1' . ucfirst($perverted),
                    $value
                );
            }
        }

        if (!empty($key) && $is_american && strtolower($key[0]) == $key[0]) {
            // Day comes after month
            switch ($key . '=' . $value) {
                case 'calendar_date=%d-%m-%Y':
                    $value = '%m-%d-%Y';
                    break;

                case 'calendar_date_verbose=%a %e%o %B %Y':
                    $value = '%a %B %e%o %Y';
                    break;

                case 'calendar_date_range_single_long=%l:%M %p (%e%o %b)':
                    $value = '%l:%M %p (%b %e%o)';
                    break;

                case 'date_regular_date=%e%o %B %Y':
                    $value = '%B %e%o %Y';
                    break;

                case 'date_no_year=%e%o %B':
                    $value = '%B %e%o';
                    break;

                case 'date_verbose_date=%a %e%o %B %Y':
                    $value = '%a %B %e%o %Y';
                    break;

                case 'locale=en-GB.UTF-8,en_GB.UTF-8,en-US.UTF-8,en_US.UTF-8,en.UTF-8,en-GB,en_GB,en-US,en_US,en':
                    $value = 'en-US.UTF-8,en_US.UTF-8,en.UTF-8,en-US,en_US,en';
                    break;

                case 'dictionary=en_GB':
                    $value = 'en_US';
                    break;

                case 'dictionary_variant=british':
                    $value = 'american';
                    break;
            }
        }

        return $value;
    }

    /**
     * Do a run-time filter. Only happens for strings marked for processing with a flag.
     *
     * @param  string $key Language string ID
     * @param  string $value Language string value
     * @param  string $flag Flag value assigned to the string
     * @param  array $parameters The parameters
     * @return string The suffix
     */
    public function run_time($key, $value, $flag, $parameters)
    {
        $flags = explode('|', $flag);

        $matches = array();

        $preserved = array();

        foreach ($flags as $flag_i => $flag) {
            if ($flag[0] == 'p' && preg_match('#^preserve=(.*)$#', $flag, $matches) != 0) {
                $preserve = $matches[1];
                $preserved[$flag_i] = $matches[1];
                $value = str_replace($preserve, 'preserve_' . strval($flag_i), $value);
            }

            // Putting in correct keypress for Mac users
            elseif ($flag == 'platform_specific') {
                if (strpos(cms_srv('HTTP_USER_AGENT'), 'Macintosh') === false) {
                    $value = str_replace('Ctrl key (Option key on a mac)', 'Ctrl key', $value);
                } else {
                    $value = str_replace('Ctrl key (Option key on a mac)', 'Option key', $value);
                }
            }

            // Putting correct content type words to generic strings, with appropriate grammar...

            elseif (preg_match('#^(resource|category|entry|content_type_module)_in_param_(\d+)$#', $flag, $matches) != 0) {
                $type = $matches[1];
                $param_num = intval($matches[2]);
                if (!empty($parameters[$param_num - 1])) {
                    $content_type = is_object($parameters[$param_num - 1]) ? $parameters[$param_num - 1]->evaluate() : $parameters[$param_num - 1];

                    require_code('content');
                    $object = get_content_object($content_type);
                    if (is_null($object)) {
                        if (preg_match('#^\w+$#', $content_type) != 0) {
                            $specific = do_lang($content_type, null, null, null, null, false);
                        } else {
                            $specific = $content_type;
                        }
                        if (is_null($specific)) {
                            $specific = strtolower($content_type);
                        } else {
                            $specific = strtolower($specific);
                        }
                    } else {
                        $info = $object->info();
                        $specific = strtolower(do_lang($info['content_type_label']));
                    }

                    $is_vowel = $specific !== '' && isset($this->vowels[$specific[0]]);
                    $article_word = $is_vowel ? 'an' : 'a';

                    if (preg_match('#[^aeiou]y$#', $specific) != 0) {
                        $specific_plural = substr($specific, 0, strlen($specific) - 1) . 'ies';
                    } else {
                        $specific_plural = $specific . 's';
                    }

                    switch ($type)
                    {
                        case 'resource':
                            $reps = array(
                                'a resource' => $article_word . ' ' . $specific,
                                'A resource' => ucfirst($article_word) . ' ' . $specific,
                                'resources' => $specific_plural,
                                'resource' => $specific,
                            );
                            break;

                        case 'category':
                            $reps = array(
                                'a category' => $article_word . ' ' . $specific,
                                'A category' => ucfirst($article_word) . ' ' . $specific,
                                'categories' => $specific_plural,
                                'category' => $specific,
                            );
                            break;

                        case 'entry':
                            $reps = array(
                                'an entry' => $article_word . ' ' . $specific,
                                'An entry' => ucfirst($article_word) . ' ' . $specific,
                                'entries' => $specific_plural,
                                'entry' => $specific,
                            );
                            break;

                        case 'content_type_module':
                            $reps = array(
                                'a content-type' => $article_word . ' ' . $specific . ' module',
                                'A content-type' => ucfirst($article_word) . ' ' . $specific . ' module',
                                'content-types' => $specific_plural . ' module',
                                'content-type' => $specific . ' module',
                            );
                            break;

                        default:
                            $reps = array();
                            break;
                    }

                    $strlen = strlen($value);
                    for ($i = 0; $i < $strlen; $i++) {
                        foreach ($reps as $from => $to) {
                            if ($value[$i] == $from[0] && substr($value, $i, strlen($from)) == $from) {
                                $value = substr($value, 0, $i) . $to . substr($value, $i + strlen($from));
                                $strlen = strlen($value);
                                $i += strlen($to) - 1;
                                continue 2;
                            }
                        }
                    }
                    //$value = str_replace(array_keys($reps), array_values($reps), $value); This doesn't work when a replacement itself might be replaced in a further iteration of $reps
                    
                }
            }
        }

        foreach ($preserved as $i => $preserve) {
            $value = str_replace('preserve_' . strval($flag_i), $preserve, $value);
        }

        return $value;
    }

    /**
     * Do a compile-time filter of the CSS code.
     *
     * @param  string $name The name of the CSS file
     * @param  string $code The CSS code
     * @return string The filtered CSS code
     */
    public function filter_css($name, $code)
    {
        return $code;
    }
}
