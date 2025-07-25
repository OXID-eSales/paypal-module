<?php

namespace OxidSolutionCatalysts\PayPalApi\Model;

use Error;
use OxidSolutionCatalysts\PayPalApi\Pui\ExperienceContext;

trait BaseModel
{
    /**
     * Customizes the payer experience during the approval process for the payment with
     * PayPal.<blockquote><strong>Note:</strong> Partners and Marketplaces might configure <code>brand_name</code>
     * and <code>shipping_preference</code> during partner account setup, which overrides the request
     * values.</blockquote>
     *
     * @var \OxidSolutionCatalysts\PayPalApi\Model\Orders\OrderExperienceContext
     */
    public $experience_context;

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return (object) array_filter((array) $this, static function ($var) {
            return isset($var);
        });
    }

    public function __set($name, $value)
    {
        throw new Error("Cant set $name on " . static::class);
    }
}
