<?php

declare(strict_types=1);

namespace dokuwiki\plugin\yearbox\src;

use Doku_Handler;
use Psr\Log\LoggerInterface;

final class SyntaxHandler
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Handle the match
     * E.g.: {{yearbox>year=2010;name=journal;size=12;ns=diary}}
     *
     */
    public function handle($match): array
    {
        global $INFO;
        $opt = [];

        // default options
        $opt['ns'] = $INFO['namespace'] ?? '';   // this namespace
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
                    $opt['recent'] = ((int)$value > 0) ? (int)$value : 0;
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
                    $this->logger->warning("Yearbox Plugin: Unknown key '$key' in '$match'");
            }
        }
        return $opt;
    }
}
