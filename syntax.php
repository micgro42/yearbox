<?php

/**
 * yearbox Plugin: provides a year calendar, with links to a new page for each day
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Symon Bent: <symonbent [at] gmail [dot] com>
 */

declare(strict_types=1);

use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Logger;
use dokuwiki\plugin\yearbox\services\pageNameStrategies\PageNameStrategy;

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_yearbox extends SyntaxPlugin
{

    /**
     * What kind of syntax is this?
     */
    public function getType()
    {
        return 'substition';
    }

    public function getPType()
    {
        return 'block';
    }

    /**
     * What modes are allowed within this mode?
     */
    public function getAllowedTypes()
    {
        return ['substition', 'protected', 'disabled', 'formatting'];
    }

    /**
     * What position in the sort order?
     */
    public function getSort()
    {
        return 125;
    }

    /**
     * Connect pattern to lexer
     */
    public function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('{{yearbox>.*?}}', $mode, 'plugin_yearbox');
    }

    /**
     * Handle the match
     * E.g.: {{yearbox>year=2010;name=journal;size=12;ns=diary}}
     *
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        global $INFO;
        $opt = [];

        // default options
        $opt['ns'] = $INFO['namespace'];   // this namespace
        $opt['size'] = 12;                 // 12px font size
        $opt['name'] = 'day';              // a boring default page name
        $opt['year'] = date('Y');          // this year
        $opt['recent'] = false;            // special 1-2 row 'recent pages' view...
        $opt['months'] = [];               // months to be displayed (csv list), e.g. 1,2,3,4... 1=Sun
        $opt['weekdays'] = [];             // weekdays which should have links (csv links)... 1=Jan
        $opt['align'] = '';                // default is centred

        $optionsString = substr($match, 10, -2);
        $args = explode(';', $optionsString);
        foreach ($args as $arg) {
            [$key, $value] = explode('=', $arg);
            switch ($key) {
                case 'year':
                    $opt['year'] = $value;
                    break;
                case 'name':
                    $opt['name'] = $value;
                    break;
                case 'fontsize':
                case 'size':
                    $opt['size'] = $value;
                    break;
                case 'ns':
                    $opt['ns'] = (strpos($value, ':') === false) ? ':' . $value : $value;
                    break;
                case 'recent':
                    $opt['recent'] = ($value > 0) ? abs($value) : 0;
                    break;
                case 'months':
                    $opt['months'] = explode(',', $value);
                    break;
                case 'weekdays':
                    $opt['weekdays'] = explode(',', $value);
                    break;
                case 'align':
                    if (in_array($value, ['left', 'right'])) {
                        $opt['align'] = $value;
                    }
                    break;
                default:
                    Logger::getInstance(Logger::LOG_DEBUG)->log(
                        "Unknown key: '$key' in '$match'"
                    );
            }
        }
        return $opt;
    }

    /**
     * Create output
     */
    public function render($mode, Doku_Renderer $renderer, $opt)
    {
        if ($mode == 'xhtml') {
            $renderer->doc .= $this->buildCalendar($opt);
            return true;
        }
        return false;
    }


    /**
     * Builds a complete HTML calendar of the year given
     * Provides a link to a page for each day of the year, with a popup abstract of page content
     *
     * $opt = array(
     *
     * @param string $year     build calendar for one year (2011), or range of years (2011,2013)
     * @param string $name     prefix for new page name, e.g diary, journal, day
     * @param int    $size     font size to use
     * @param string $ns       root namespace for new page names
     * @param int    $recent   previous days that must be visible
     * @param array  $months   which months are visible (1-12), 1=Jan, 2=Feb, etc
     * @param array  $weekdays which weekdays should have links (1-7), 1=Sun, 2=Mon, etc...
     *                         }
     *
     * @return string   Complete marked up calendar table
     */
    private function buildCalendar($opt)
    {
        $day_names = $this->getLang('yearbox_days');
        $cal = '';

        [$years, $first_weekday, $table_cols, $today] = $this->defineCalendar($opt);
        end($years);
        $last_year = key($years);

        // initial CSS
        $font_css = ($opt['size'] != 0) ? ' style="font-size:' . $opt['size'] . 'px;"' : '';
        if ($opt['align'] == 'left') {
            $align = ' class=left';
        } elseif ($opt['align'] == 'right') {
            $align = ' class=right';
        } else {
            $align = '';
        }
        $cal .= '<div class="yearbox"' . $font_css . '><table' . $align . '><tbody>';

        foreach ($years as $year_num => $year) {
            // display the year and day-of-week header
            $cal .= '<tr class="yr-header">';
            for ($col = 0; $col < $table_cols; $col++) {
                $weekday_num = ($col + $first_weekday) % 7;       // current day of week as a number
                if ($col == 0) {
                    $cal .= '<th class="plain">' . $year_num . '</th>';
                }
                $h = $day_names[$weekday_num];
                $cal .= '<th>' . $h . '</th>';
            }
            $cal .= '</tr>';

            foreach ($year as $mth_num => $month) {
                $cal .= $this->getMonthHTML(
                    $month,
                    $mth_num,
                    $opt,
                    $year_num,
                    $table_cols,
                    $first_weekday,
                    $today
                );
            }
            // separator between years in a range
            if ($year_num != $last_year) {
                $cal .= '<tr class="blank"><td></td></tr>';
            }
        }

        $cal .= '</tbody></table></div><div class="clearer"></div>';
        return $cal;
    }

    /**
     * Get the HTML for one table-row, representing one month
     *
     * @param $month
     * @param $mth_num
     * @param $opt
     * @param $year_num
     * @param $table_cols
     * @param $first_weekday
     * @param $today
     *
     * @return string
     */
    protected function getMonthHTML(
        $month,
        $mth_num,
        $opt,
        $year_num,
        $table_cols,
        $first_weekday,
        $today
    ) {
        $cal = '<tr>';
        // insert month name into first column of row
        $cal .= $this->getMonthNameHTML($mth_num);
        $cur_day = 0;
        for ($col = 0; $col < $table_cols; $col++) {
            $weekday_num = ($col + $first_weekday) % 7;       // current day of week as a number

            // current day is only valid if within the month's days, and at the correct starting day
            if (($cur_day > 0 && $cur_day < $month['len']) || ($col < 7 && $weekday_num == $month['start'])) {
                $cur_day++;
                $cal .= $this->getDayHTML($cur_day, $mth_num, $today, $year_num, $weekday_num, $opt);
            } else {
                $cur_day = 0;
                $cal .= $this->getEmptyCellHTML();
            }
        }
        $cal .= '</tr>';

        return $cal;
    }

    /**
     * @param int   $cur_day     Day of the month
     * @param int   $mth_num     Month 1..12
     * @param int   $today       ts today midnight FIXME
     * @param int   $year_num    year as YYYY
     * @param int   $weekday_num day of the week 0..6 (0=sunday, 6=saturday)
     * @param array $opt         config from handler
     *
     * @return string
     */
    public function getDayHTML($cur_day, $mth_num, $today, $year_num, $weekday_num, $opt)
    {
        if (!$this->isWeekdayToBePrinted($weekday_num, $opt)) {
            return $this->getEmptyCellHTML();
        }

        global $conf;
        $is_weekend = $weekday_num === 0 || $weekday_num === 6;
        $day_css = ($is_weekend) ? ' class="wkend"' : '';
        $day_fmt = sprintf("%02d", $cur_day);
        $month_fmt = sprintf("%02d", $mth_num);
        $pagenameService = PageNameStrategy::getPagenameStategy($this->getConf('namestructure'));
        $id = $pagenameService->getPageId($opt['ns'], $year_num, $month_fmt, $day_fmt, $opt['name']);
        $current = mktime(0, 0, 0, $mth_num, $cur_day, $year_num);
        if ($current == $today) {
            $day_css = ' class="today"';
        }

        $link = $this->getDayLinkHTML($id, $day_fmt, $conf[ 'userewrite' ]);
        return '<td' . $day_css . '>' . $link . '</td>';
    }

    /**
     * Determine if the given weekday should be printed or be an empty cell
     *
     * @param $weekday_num
     * @param $opt
     *
     * @return bool
     */
    protected function isWeekdayToBePrinted($weekday_num, $opt)
    {
        if (empty($opt['weekdays'])) {
            return true;
        }
        return in_array($weekday_num, $opt['weekdays']);
    }

    /**
     * Get the HTML for a header cell with the month name
     *
     * @param $mth_num
     *
     * @return string
     */
    protected function getMonthNameHTML($mth_num)
    {
        $month_names = [
            $this->getLang('yearbox_months_jan'),
            $this->getLang('yearbox_months_feb'),
            $this->getLang('yearbox_months_mar'),
            $this->getLang('yearbox_months_apr'),
            $this->getLang('yearbox_months_may'),
            $this->getLang('yearbox_months_jun'),
            $this->getLang('yearbox_months_jul'),
            $this->getLang('yearbox_months_aug'),
            $this->getLang('yearbox_months_sep'),
            $this->getLang('yearbox_months_oct'),
            $this->getLang('yearbox_months_nov'),
            $this->getLang('yearbox_months_dec'),
        ];
        $alt_css = ($mth_num % 2 == 0) ? ' class="alt"' : '';
        return '<th' . $alt_css . '>' . $month_names[$mth_num - 1] . '</th>';
    }

    /**
     * Get the HTML for an empty cell
     *
     * @return string
     */
    protected function getEmptyCellHTML()
    {
        return '<td class="blank">&nbsp;&nbsp;&nbsp;</td>';
    }


    /**
     * establish list of valid months and days, ready for building the visible calendar
     *
     * @param array $opt users options
     */
    private function defineCalendar($opt)
    {
        $years = [];

        $table_cols = 0;
        $first_weekday = 6;

        $year_range = explode(',', $opt['year']);
        $today = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));

        // work out the date range first
        if ($opt['recent'] > 0) {
            // recent days (matching at least no. of recent days given; shows complete months only)
            $mth_last = (int)date('n');
            $yr_last = (int)date('Y');
            $prev_date = $today - ($opt['recent'] * 12 * 60 * 60);
            $mth_first = (int)date('n', $prev_date);
            $yr_first = (int)date('Y', $prev_date);
            $mth_last += ($yr_last - $yr_first) * 12;
        } elseif (count($year_range) == 2) {
            // if user provides two years: first -> last (inclusive)
            $mth_first = 1;
            [$yr_first, $yr_last] = $year_range;
            $mth_last = 12 + ($yr_last - $yr_first) * 12;
        } else {
            // plain old one year calender
            $mth_first = 1;
            $mth_last = 12;
            $yr_first = $yr_last = $opt['year'];
        }
        $show_all_mths = empty($opt['months']);

        // first get start day for each month, and length of month,
        // exact no. of columns needed, and the starting day of week
        for ($mth = $mth_first; $mth <= $mth_last; $mth++) {
            $mth_num = ($mth - 1) % 12 + 1; // real month number (1-12)

            // only consider displayed months when calculating column size
            if ($show_all_mths || in_array($mth_num, $opt['months'])) {
                $year = $yr_first + floor(($mth - 1) / 12); // allow for year overlaps
                $start = date('w', mktime(0, 0, 0, $mth_num, 1, (int)$year));
                $len = date('j', mktime(0, 0, 0, $mth_num + 1, 0, (int)$year));

                // save the first weekday (0-6; 0=Sun) and length (days) of this month
                $years[$year][$mth_num] = ['start' => $start, 'len' => $len];

                // max number of table columns needed (not including col for months!)
                $table_cols = ($table_cols < ($start + $len)) ? $start + $len : $table_cols;

                // find the lowest day of week (i.e. Sun = 0, Mon = 1, etc...)
                // this determines which day of week to begin column headers with
                $first_weekday = ($first_weekday > $start) ? $start : $first_weekday;
            }
        }
        // final total columns needed in HTML table
        $table_cols -= $first_weekday;

        return [$years, $first_weekday, $table_cols, $today];
    }

    private function wikilinkPreviewPopup($id, $name)
    {
        // swap normal link title (popup) for a more useful preview
        $link = html_wikilink($id, $name);
        $meta = p_get_metadata($id, false, true);
        $abstract = $meta['description']['abstract'] . '… ' . "\nEdited: " . date('Y-M-d', $meta['date']['modified']);
        $preview = htmlentities($abstract, ENT_QUOTES, 'UTF-8');
        $link = preg_replace('/title=\".+?\"/', 'title="' . $preview . '"', $link, 1);
        return $link;
    }

    /**
     * @param string $id
     * @param string $day_fmt
     * @param        $userewrite
     *
     * @return string|string[]|null
     */
    private function getDayLinkHTML(string $id, string $day_fmt, $userewrite)
    {
        if (page_exists($id)) {
            return $this->wikilinkPreviewPopup($id, $day_fmt);
        }

        $link = html_wikilink($id, $day_fmt);
        // skip the "do you want to create this page" bit
        $sym = ($userewrite) ? '?' : '&amp;';
        return preg_replace('/\" class/', $sym . 'do=edit" class', $link, 1);
    }
}
