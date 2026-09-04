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

    /**
     * Resolves the locale in the notation of the oscPayPalLocales setting, which is what the JS SDK
     * expects ("de_CH"). The customer country is honoured when a matching regional locale is
     * configured, so a german speaking customer in Switzerland gets "de_CH" while a customer in
     * Germany keeps "de_DE". Without the country the first configured entry of the language wins,
     * which turns the order of the setting into a shop wide switch.
     *
     * Lookup order: language plus country, then language only, then the first configured entry.
     */
    public function mapLanguageToLocale(string $language, string $countryCode = ''): string
    {
        $supportedLocales = $this->moduleSettings->getSupportedLocales();

        if ($language !== '' && $countryCode !== '') {
            $wanted = strtolower($language) . '_' . strtoupper($countryCode);
            foreach ($supportedLocales as $locale) {
                if (strcasecmp(str_replace('-', '_', $locale), $wanted) === 0) {
                    return $locale;
                }
            }
        }

        foreach ($supportedLocales as $locale) {
            if (stripos($locale, $language) === 0) {
                return $locale;
            }
        }

        return $supportedLocales[0] ?? '';
    }

    /**
     * Resolves the locale for PayPal API requests (experience_context.locale), which expects BCP 47
     * with a hyphen ("de-CH") while oscPayPalLocales holds the underscore notation the JS SDK needs
     * ("de_CH"). Same lookup as mapLanguageToLocale(), so both sides of the integration always agree
     * on the locale, only the notation differs. Returns an empty string when nothing usable is
     * configured - callers must then omit the locale instead of sending an empty one.
     */
    public function mapLanguageToBcp47Locale(string $language, string $countryCode = ''): string
    {
        return $this->normalizeToBcp47($this->mapLanguageToLocale($language, $countryCode));
    }

    /**
     * Turns a configured locale into the notation the PayPal API accepts, where the region part is
     * optional. Anything that is not a language code is dropped, so a typo in the module setting
     * cannot end up in an API request.
     */
    private function normalizeToBcp47(string $locale): string
    {
        $parts = explode('-', str_replace('_', '-', trim($locale)), 2);

        // PayPal only accepts a two letter ISO 639-1 language, so anything else is a typo
        $language = strtolower($parts[0]);
        if (!preg_match('/^[a-z]{2}$/', $language)) {
            return '';
        }

        $region = isset($parts[1]) ? strtoupper($parts[1]) : '';

        return preg_match('/^[A-Z]{2}$/', $region) ? $language . '-' . $region : $language;
    }
}
