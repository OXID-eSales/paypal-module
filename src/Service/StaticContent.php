<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Service;

use PDO;
use Doctrine\DBAL\Query\QueryBuilder;
use OxidEsales\Eshop\Core\Registry as EshopRegistry;
use OxidEsales\Eshop\Core\Config as EshopCoreConfig;
use OxidSolutionCatalysts\PayPal\Core\PayPalDefinitions;
use OxidEsales\Eshop\Application\Model\Content as EshopModelContent;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;

class StaticContent
{
    /** @var QueryBuilderFactoryInterface */
    private $queryBuilderFactory;

    /** @var ContextInterface */
    private $context;

    /** @var EshopCoreConfig */
    private $config;

    public function __construct(
        QueryBuilderFactoryInterface $queryBuilderFactory,
        ContextInterface             $context,
        EshopCoreConfig              $config
    )
    {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->context = $context;
        $this->config = $config;
    }

    public function addStaticContents(): void
    {
        foreach (PayPalDefinitions::getPayPalStaticContents() as $content) {
            foreach ($this->getLanguageIds() as $langId => $langAbbr) {
                $contentModel = $this->getContentModel($content['oxloadid'], $langId);
                $contentModel->assign(
                    [
                        'oxloadid'  => $content['oxloadid'],
                        'oxactive'  => $content['oxactive'],
                        'oxtitle'   => isset($content['oxtitle_' . $langAbbr]) ? $content['oxtitle_' . $langAbbr] : '',
                        'oxcontent' => isset($content['oxcontent_' . $langAbbr]) ? $content['oxcontent_' . $langAbbr] : '',
                    ]
                );
                $contentModel->save();
            }
        }
    }

    protected function getContentModel(string $ident, int $languageId): EshopModelContent
    {
        $content = oxNew(EshopModelContent::class);
        if ($content->loadByIdent($ident)) {
            $content->loadInLang($languageId, $content->getId());
        }

        return $content;
    }

    /**
     * get the language-IDs
     */
    protected function getLanguageIds(): array
    {
        return EshopRegistry::getLang()->getLanguageIds();
    }

    protected function getActiveCountries(): array
    {
        $result = [];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->queryBuilderFactory->create();
        $fromDb = $queryBuilder
            ->select('oxid, oxisoalpha2')
            ->from('oxcountry')
            ->where('oxactive = 1')
            ->execute()
            ->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fromDb as $row) {
            $result[$row['oxid']] = $row['oxisoalpha2'];
        }

        return $result;
    }
}
