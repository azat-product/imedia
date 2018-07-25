<?php

class PluginBbsTitleFilterP05c9ceTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
    }

    protected function _after()
    {
    }

    protected $fixture;

    protected function setUp()
    {
        $this->fixture = \config::get('app.internal.tests.extension');
    }

    protected function tearDown()
    {
        $this->fixture = NULL;
    }


    # Tests

    /**
     * @dataProvider providerMbUcfirst
     */
    public function testMbUcfirst($a, $out)
    {
        $this->assertEquals($out, $this->fixture->mb_ucfirst($a));
    }

    /**
     * @dataProvider providerMbStrReplace
     */
    public function testMbStrReplace($a, $b, $c, $out)
    {
        $this->assertEquals($out, $this->fixture->mb_str_replace($a, $b, $c));
    }

    /**
     * @dataProvider providerSpaceComma
     */
    public function testSpaceComma($a, $out)
    {
        $this->assertEquals($out, $this->fixture->spaceComma($a));
    }

    /**
     * @dataProvider providerLowerCase
     */
    public function testLowerCase($a, $b, $out)
    {
        $this->assertEquals($out, $this->fixture->lowerCase($a, $b));
    }

    /**
     * @dataProvider providerSingleRepeatedSymbols
     */
    public function testSingleRepeatedSymbols($a, $b, $c, $out)
    {
        $this->assertEquals($out, $this->fixture->singleRepeatedSymbols($a, $b, $c));
    }

    /**
     * @dataProvider providerRemoveEmoji
     */
    public function testRemoveEmoji($a, $out)
    {
        $this->assertEquals($out, $this->fixture->removeEmoji($a));
    }


    # Data Providers

    /**
     *  Данные для тестирования mb_ucfirst()
     * @return array данные для тестов
     */
    public function providerMbUcfirst()
    {
        return array (
            array ('test string', 'Test string'),
            array ('test', 'Test'),
            array ('тест', 'Тест'),
            array (' тест', ' тест'),
            array ('!тест', '!тест'),
        );
    }

    /**
     *  Данные для тестирования mbStrReplace()
     * @return array данные для тестов
     */
    public function providerMbStrReplace()
    {
        return array (
            array ('test', 'dest', 'test string', 'dest string'),
            array ('t', 'T', 'test string', 'TesT sTring'),
            array ('test string', '', 'test string', ''),
            array (' ', ', ', 'test string', 'test, string'),
            array ('🆗', '🐟', '🆗,🆗🆗', '🐟,🐟🐟'),
        );
    }

    /**
     *  Данные для тестирования spaceComma()
     * @return array данные для тестов
     */
    public function providerSpaceComma()
    {
        return array (
            array ('test,string', 'test, string'),
            array ('✅️,✅️,✅️', '✅️, ✅️, ✅️'),
            array ('🍔,string', '🍔, string')
        );
    }

    /**
     *  Данные для тестирования lowerCase()
     * @return array данные для тестов
     */
    public function providerLowerCase()
    {
        return array (
            array ('AAA', 3, 'Aaa'),
            array ('BBB', 4, 'BBB'),
            array ('ЦЦЦ', 3, 'Ццц'),
            array ('Ц🚂🚂', 3, 'Ц🚂🚂'),
            array ('🔃Г🚂🚂', 3, '🔃г🚂🚂'),
        );
    }

    /**
     *  Данные для тестирования singleRepeatedSymbols()
     * @return array данные для тестов
     */
    public function providerSingleRepeatedSymbols()
    {
        return array (
            array ('Special1', '/', false, 'Special1'),
            array ('A!!B@@C##D%%E^^F&&G**H((I))J}}K{{L||M""N::O<<P>>Q??R!!S№№T__U++V==W//X\\\\',
                   '!@#$%^&*()}{|":<>?!№_+=/\\', false,
                   'A!B@C#D%E^F&G*H(I)J}K{L|M"N:O<P>Q?R!S№T_U+V=W/X\\'),
            array ('AAA', 'A', false, 'A'),
            array ('BBB', 'B', false, 'B'),
            array ('Aaa', 'A', true, 'Aaa'),
            array ('ШШШ', 'Ш', true, 'Ш'),
            array ('2222', '2', true, '2'),
            array ('УУУ,УУУ', 'У', true, 'У,У'),
            array ('УУУ,УУУ', 'у', true, 'УУУ,УУУ'),
            array ('🔦🔦🔦🔦', '🔦', true, '🔦'),
            array ('🔦🔦🔦🔦', '🔦', false, '🔦'),
            array ('⚽️⚽️,⚽️⚽️', '⚽️', true, '⚽️,⚽️'),
            array ('⚽️⚽️,⚽️⚽️', '⚽️', false, '⚽️,⚽️'),
        );
    }

    /**
     *  Данные для тестирования removeEmoji()
     * @return array данные для тестов
     */
    public function providerRemoveEmoji()
    {
        return array (
            array ('✅️', ''),
            array ('🍔🍔', ''),
            array ('▶️▶️▶️', ''),
            array ('d🚂,', 'd,'),
            array ('🔃,🔃sdf🔃🔃!', ',sdf!'),
            array ('🐟 🐟, 🐟', ' , '),
            array (' 🆗🆗🆗!', ' !'),
        );
    }
}