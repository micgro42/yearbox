<?php

namespace dokuwiki\plugin\yearbox\services\pageNameStrategies;

class YearMonthCombinedNS extends PageNameStrategy
{

    public function getPageId($baseNS, $year, $month, $day, $name)
    {
        $pagename = ($name ? "$name-" : '') . "$year-$month-$day";
        return "$baseNS:$year-$month:$pagename";
    }
}
