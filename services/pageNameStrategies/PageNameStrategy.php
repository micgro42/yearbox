<?php

namespace dokuwiki\plugin\yearbox\services\pageNameStrategies;

abstract class PageNameStrategy
{
    public static function getPagenameStategy($namingStructure)
    {
        switch ($namingStructure) {
            case 'separatedCompletely':
            case 2:
                return new CompletelySeparated();
            case 'YearMonthSeperatedNS':
            case 1:
                return new YearMonthSeperatedNS();
            case 'YearNS':
                return new YearNS();
            default:
                return new YearMonthCombinedNS();
        }
    }

    abstract public function getPageId($baseNS, $year, $month, $day, $name);
}
