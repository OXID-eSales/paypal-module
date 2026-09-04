<?php

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Service;

use OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper;
use OxidSolutionCatalysts\PayPal\Service\ModuleSettings;
use PHPUnit\Framework\TestCase;

class LanguageLocaleMapperTest extends TestCase
{
    private const SUPPORTED_LOCALES = ['de_DE', 'en_US'];
    /**
     * @covers \OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper::mapLanguageToLocale
     * @dataProvider getTestData
     */
    public function testMapLanguageToLocale(string $givenLanguage, string $expectedLocale)
    {
        $moduleSettings = $this->getMockBuilder(ModuleSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSupportedLocales'])
            ->getMock();
        $moduleSettings->method('getSupportedLocales')
            ->willReturn(self::SUPPORTED_LOCALES);

        $mapper = new LanguageLocaleMapper($moduleSettings);

        $this->assertEquals(
            $expectedLocale,
            $mapper->mapLanguageToLocale($givenLanguage)
        );
    }

    /**
     * The JS SDK side of the same lookup: notation stays as configured, but the customer country is
     * honoured, so the order of the setting is no longer a shop wide switch.
     *
     * @covers \OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper::mapLanguageToLocale
     * @dataProvider getLocaleWithCountryTestData
     */
    public function testMapLanguageToLocaleWithCountry(
        array $supportedLocales,
        string $givenLanguage,
        string $givenCountry,
        string $expectedLocale
    ) {
        $moduleSettings = $this->getMockBuilder(ModuleSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSupportedLocales'])
            ->getMock();
        $moduleSettings->method('getSupportedLocales')
            ->willReturn($supportedLocales);

        $mapper = new LanguageLocaleMapper($moduleSettings);

        $this->assertEquals(
            $expectedLocale,
            $mapper->mapLanguageToLocale($givenLanguage, $givenCountry)
        );
    }

    public function getLocaleWithCountryTestData(): array
    {
        return [
            [ // regional locale configured and matching the customer country: preferred
                ['de_DE', 'de_CH', 'en_US'],
                'de',
                'CH',
                'de_CH',
            ],
            [ // the regional locale listed first no longer decides for customers of other countries
                ['de_CH', 'de_DE', 'en_US'],
                'de',
                'DE',
                'de_DE',
            ],
            [ // no regional locale configured: language match wins
                self::SUPPORTED_LOCALES,
                'de',
                'CH',
                'de_DE',
            ],
            [ // country matching another language must not beat the shop language
                ['de_CH', 'fr_CH'],
                'fr',
                'CH',
                'fr_CH',
            ],
            [ // unsupported language falls back to the first entry
                self::SUPPORTED_LOCALES,
                'fr',
                'CH',
                'de_DE',
            ],
            [ // unknown customer country behaves like before
                ['de_CH', 'de_DE'],
                'de',
                '',
                'de_CH',
            ],
            [ // emptied setting: caller has to omit the locale
                [],
                'de',
                'CH',
                '',
            ],
        ];
    }

    /**
     * @covers \OxidSolutionCatalysts\PayPal\Service\LanguageLocaleMapper::mapLanguageToBcp47Locale
     * @dataProvider getBcp47TestData
     */
    public function testMapLanguageToBcp47Locale(
        array $supportedLocales,
        string $givenLanguage,
        string $givenCountry,
        string $expectedLocale
    ) {
        $moduleSettings = $this->getMockBuilder(ModuleSettings::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSupportedLocales'])
            ->getMock();
        $moduleSettings->method('getSupportedLocales')
            ->willReturn($supportedLocales);

        $mapper = new LanguageLocaleMapper($moduleSettings);

        $this->assertEquals(
            $expectedLocale,
            $mapper->mapLanguageToBcp47Locale($givenLanguage, $givenCountry)
        );
    }

    public function getBcp47TestData(): array
    {
        return [
            [ // underscore notation of the setting is converted to the hyphen the api expects
                self::SUPPORTED_LOCALES,
                'de',
                'DE',
                'de-DE',
            ],
            [ // regional locale configured and matching the customer country: preferred
                ['de_DE', 'de_CH', 'en_US'],
                'de',
                'CH',
                'de-CH',
            ],
            [ // no regional locale configured: language match wins, never "ch-CH"
                self::SUPPORTED_LOCALES,
                'de',
                'CH',
                'de-DE',
            ],
            [ // country matching another language must not beat the shop language
                ['de_CH', 'fr_CH'],
                'fr',
                'CH',
                'fr-CH',
            ],
            [ // unsupported language falls back to the first entry
                self::SUPPORTED_LOCALES,
                'fr',
                'CH',
                'de-DE',
            ],
            [ // no country given at all
                self::SUPPORTED_LOCALES,
                'en',
                '',
                'en-US',
            ],
            [ // sloppy notation in the setting is normalized
                ['de-ch'],
                'de',
                'CH',
                'de-CH',
            ],
            [ // language without region part is valid for the api and kept as is
                ['de'],
                'de',
                'CH',
                'de',
            ],
            [ // garbage in the setting is dropped instead of being sent to the api
                ['not-a-locale'],
                'de',
                'CH',
                '',
            ],
            [ // emptied setting: caller has to omit the locale
                [],
                'de',
                'CH',
                '',
            ],
        ];
    }

    public function getTestData(): array
    {
        return [
            [ // first supported language
                'de',
                'de_DE',
            ],
            [ // second supported language
                'en',
                'en_US',
            ],
            [ // default language, because fr_FR is not supported
                'fr',
                'de_DE',
            ],
        ];
    }
}
