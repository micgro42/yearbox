<?php

declare(strict_types=1);

namespace dokuwiki\plugin\yearbox\test;

use DokuWikiTest;

/**
 * Tests the weekday letters in the calendar header for different languages
 *
 * @group plugin_yearbox
 * @group plugins
 */
final class WeekdayHeaderTest extends DokuWikiTest
{
    protected $pluginsEnabled = ['yearbox'];

    /**
     * The first week of the header for January 2018 in different languages.
     *
     * January 2018 starts on a Monday. Thus the header starts with Monday.
     * Languages that do not use ASCII show that one letter can use more than one byte.
     *
     * @return array<string, array{string, string[]}>
     */
    public static function provideFirstWeek(): array
    {
        return [
            'en (one byte for each letter)' => ['en', ['M', 'T', 'W', 'T', 'F', 'S', 'S']],
            'ru (Cyrillic)' => ['ru', ['П', 'В', 'С', 'Ч', 'П', 'С', 'В']],
            'uk (Cyrillic)' => ['uk', ['П', 'В', 'С', 'Ч', 'П', 'С', 'Н']],
            'mk (Cyrillic)' => ['mk', ['П', 'В', 'С', 'Ч', 'П', 'С', 'Н']],
            'az (Latin with diacritics)' => ['az', ['B', 'Ç', 'Ç', 'C', 'C', 'Ş', 'S']],
        ];
    }

    /**
     * The languages of provideFirstWeek(), without the expected letters
     *
     * @return array<string, array{string}>
     */
    public static function provideLanguages(): array
    {
        $languages = [];
        foreach (self::provideFirstWeek() as $name => [$language]) {
            $languages[$name] = [$language];
        }

        return $languages;
    }

    /**
     * @dataProvider provideFirstWeek
     *
     * @param string   $language      the wiki language
     * @param string[] $expectedWeek  the first seven weekday letters
     */
    public function testFirstWeekOfHeader(string $language, array $expectedWeek): void
    {
        $headers = $this->renderWeekdayHeaders($language);

        self::assertCount(31, $headers, 'January 2018 needs 31 columns');
        self::assertSame($expectedWeek, array_slice($headers, 0, 7));
    }

    /**
     * Each header cell must hold one complete character.
     *
     * A byte offset into the language string cuts a multi-byte letter into parts.
     * This makes cells that are not valid UTF-8.
     *
     * @dataProvider provideLanguages
     *
     * @param string $language the wiki language
     */
    public function testEachHeaderCellIsOneCharacter(string $language): void
    {
        $headers = $this->renderWeekdayHeaders($language);

        foreach ($headers as $column => $header) {
            self::assertSame(
                1,
                preg_match('/^.$/u', $header),
                sprintf(
                    'Column %d of language "%s" is not one UTF-8 character but the bytes %s',
                    $column,
                    $language,
                    bin2hex($header)
                )
            );
        }
    }

    /**
     * Render the calendar for January 2018 and return the weekday letters of the header
     *
     * @param string $language the wiki language
     *
     * @return string[] one entry for each column
     */
    private function renderWeekdayHeaders(string $language): array
    {
        global $conf, $INFO;
        $conf['lang'] = $language;
        $INFO['namespace'] = '';

        // Ask for a new instance. An instance from an earlier test keeps its language.
        $plugin = plugin_load('syntax', 'yearbox', true);
        self::assertNotNull($plugin, 'The yearbox plugin must load');

        $opt = $plugin->handle('{{yearbox>year=2018;months=1}}', 0, 0, new \Doku_Handler());
        $renderer = p_get_renderer('xhtml');
        $plugin->render('xhtml', $renderer, $opt);

        return self::extractWeekdayHeaders($renderer->doc);
    }

    /**
     * Get the weekday cells of the header row
     *
     * The regular expressions do not use the "u" modifier. Broken bytes must stay in the
     * result, because the test must show them.
     *
     * @param string $html the calendar HTML
     *
     * @return string[] one entry for each column
     */
    private static function extractWeekdayHeaders(string $html): array
    {
        $row = [];
        self::assertSame(
            1,
            preg_match('#<tr class="yr-header">(.*?)</tr>#', $html, $row),
            'The calendar must have a header row'
        );

        // The cell with the year has a class. Only the weekday cells are plain.
        $cells = [];
        preg_match_all('#<th>(.*?)</th>#', $row[1], $cells);

        return $cells[1];
    }
}
