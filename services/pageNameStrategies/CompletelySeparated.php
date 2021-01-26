<?php

namespace dokuwiki\plugin\yearbox\services\pageNameStrategies;

class CompletelySeparated extends PageNameStrategy
{

    public function getPageId($baseNS, $year, $month, $day, $name)
    {
        return "$baseNS:$year:$month:$day";
    }
}
