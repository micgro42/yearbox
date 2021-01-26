<?php

namespace dokuwiki\plugin\yearbox\services\pageNameStrategies;

class YearNS extends PageNameStrategy
{

    public function getPageId($baseNS, $year, $month, $day, $name)
    {
        $pagename = ($name ? "$name-" : '') . "$year-$month-$day";
        return "$baseNS:$year:$pagename";
    }
}
