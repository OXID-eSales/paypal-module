<?php

namespace OxidSolutionCatalysts\PayPal\Service;

/**
 * PSPAYPAL-653: some units need to have a locale, but in oxid the user is asked for a language without location part.
 * So we introduced a PayPal configuration oscPayPalLocales with supported locales. If we find a language in the
 * language part of the supported locales. We use this if not we just use the first locale.
 */
class LanguageLocaleMapper
{
    /**
     * @var ModuleSettings
     */
    private $moduleSettings;

    public function __construct(ModuleSettings $moduleSettings)
    {
        $this->moduleSettings = $moduleSettings;
    }

    public function mapLanguageToLocale(string $language): string
    {
        $supportedLocales = $this->moduleSettings->getSupportedLocales();
        foreach ($supportedLocales as $locale) {
            if (stripos($locale, $language) === 0) {
                return $locale;
            }
        }

        return $supportedLocales[0];
    }
}
