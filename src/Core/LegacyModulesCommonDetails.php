<?php

namespace OxidSolutionCatalysts\PayPal\Core;

/*
use OxidEsales\Eshop\Core\Module\Module;
*/
use OxidEsales\Eshop\Core\Registry;
use OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
use OxidEsales\EshopCommunity\Internal\Framework\Module\Setup\Bridge\ModuleActivationBridgeInterface;
use OxidSolutionCatalysts\PayPal\Traits\ServiceContainer;

class LegacyModulesCommonDetails
{
    use ServiceContainer;

    /** @var string ID as found in metadata.php */
    protected $legacyModuleId = 'empty';

    /** @var string Getter to return the module ID */
    public function getLegacyModuleId(): string
    {
        return $this->legacyModuleId;
    }

    /**
     * Determines whether the legacy PayPal module with the ID in $legacyModuleId is active
     * @return bool
     */
    public function isLegacyModuleActive(): bool
    {
        /*
        $oepaypalModule = oxNew(Module::class);
        if ($oepaypalModule->load($this->legacyModuleId))
        {
            return $oepaypalModule->isActive();
        }

        return false;
        */

        $container = ContainerFactory::getInstance()->getContainer();
        $moduleActivationBridge = $container->get(ModuleActivationBridgeInterface::class);

        return $moduleActivationBridge->isActive(
            $this->legacyModuleId,
            Registry::getConfig()->getShopId()
        );
    }
}
