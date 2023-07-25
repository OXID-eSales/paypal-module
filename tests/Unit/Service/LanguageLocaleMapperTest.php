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
