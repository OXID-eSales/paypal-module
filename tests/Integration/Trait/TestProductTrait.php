<?php

namespace OxidSolutionCatalysts\PayPal\Tests\Integration\Trait;

use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProvider;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactory;

trait TestProductTrait
{
    private function getTestProductOxid(): string
    {
        $connectionProvider = new ConnectionProvider();

        $queryBuilderFactory = (new QueryBuilderFactory($connectionProvider))->create();
        $queryBuilderFactory->select('oxid')
            ->from('oxarticles')
            ->where('oxactive = :active')
            ->andWhere('oxstock > 0')
            ->setParameter('active', 1, \PDO::PARAM_INT);

        $productOxid = $queryBuilderFactory->execute()->fetchOne();

        if (!$productOxid) {
            $productOxid = '1574d80473f64f9d73a295da4482aecd';
            $oProduct = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
            $oProduct->assign([
                'oxid'             => $productOxid,
                'oxmapid'          => 20,
                'oxshopid'         => 1,
                'oxparentid'       => 'de01cfd9287e9c4af7f24ea907c8a0e6',
                'oxactive'         => 1,
                'oxhidden'         => 0,
                'oxactivefrom'     => '0000-00-00 00:00:00',
                'oxactiveto'       => '0000-00-00 00:00:00',
                'oxartnum'         => '9bcf4-2',
                'oxean'            => '',
                'oxdistean'        => '',
                'oxmpn'            => '',
                'oxtitle'          => '',
                'oxshortdesc'      => '',
                'oxprice'          => 24.99,
                'oxblfixedprice'   => 0,
                'oxpricea'         => 0,
                'oxpriceb'         => 0,
                'oxpricec'         => 0,
                'oxbprice'         => 0,
                'oxtprice'         => 0,
                'oxunitname'       => '',
                'oxunitquantity'   => 0,
                'oxexturl'         => '',
                'oxurldesc'        => '',
                'oxurlimg'         => '',
                'oxvat'            => null, // corresponds to SQL NULL
                'oxthumb'          => '',
                'oxicon'           => '',
                'oxpic1'           => '',
                'oxpic2'           => '',
                'oxpic3'           => '',
                'oxpic4'           => '',
                'oxpic5'           => '',
                'oxpic6'           => '',
                'oxpic7'           => '',
                'oxpic8'           => '',
                'oxpic9'           => '',
                'oxpic10'          => '',
                'oxpic11'          => '',
                'oxpic12'          => '',
                'oxweight'         => '',
                'oxstock'          => 9,
                'oxstockflag'      => 1,
                'oxstocktext'      => '',
                'oxnostocktext'    => '',
                'oxdelivery'       => '0000-00-00',
                'oxinsert'         => '2022-11-22',
                'oxtimestamp'      => '2023-03-29 12:30:12',
                'oxlength'         => 0,
                'oxwidth'          => 0,
                'oxheight'         => 0,
                'oxfile'           => '',
                'oxsearchkeys'     => '',
                'oxtemplate'       => '',
                'oxquestionemail'  => '',
                'oxissearch'       => 1,
                'oxisconfigurable' => 0,
                'oxvarname'        => '',
                'oxvarstock'       => 0,
                'oxvarcount'       => 0,
                'oxvarselect'      => '',
                'oxvarminprice'    => 0,
                'oxvarmaxprice'    => 0,
                'oxvarname_1'      => 'M',
                'oxvarselect_1'    => 0,
                'oxvarname_2'      => 0,
                'oxvarselect_2'    => 0,
                'oxvarname_3'      => '',
                'oxvarselect_3'    => '',
                'oxtitle_1'        => 'M',
                'oxshortdesc_1'    => '',
                'oxurldesc_1'      => '',
                'oxsearchkeys_1'   => '',
                'oxtitle_2'        => '',
                'oxshortdesc_2'    => '',
                'oxurldesc_2'      => '',
                'oxsearchkeys_2'   => '',
                'oxtitle_3'        => '',
                'oxshortdesc_3'    => '',
                'oxurldesc_3'      => '',
                'oxsearchkeys_3'   => '',
                'oxbundleid'       => '',
                'oxfolder'         => '',
                'oxsubclass'       => 'oxarticle',
                'oxstocktext_1'    => '',
                'oxstocktext_2'    => '',
                'oxstocktext_3'    => '',
                'oxnostocktext_1'  => '',
                'oxnostocktext_2'  => '',
                'oxnostocktext_3'  => '',
                'oxsort'           => 200,
                'oxsoldamount'     => 0,
                'oxnonmaterial'    => 0,
                'oxfreeshipping'   => 0,
                'oxremindactive'   => 0,
                'oxremindamount'   => 0,
                'oxamitemid'       => '',
                'oxamtaskid'       => '0',
                'oxvendorid'       => '',
                'oxmanufacturerid' => '',
                'oxskipdiscounts'  => 0,
                'oxorderinfo'      => '',
                'oxpixiexport'     => 0,
                'oxpixiexported'   => '',
                'oxvpe'            => 0,
                'oxrating'         => 0,
                'oxratingcnt'      => 0,
                'oxmindeltime'     => '',
                'oxmaxdeltime'     => 0,
                'oxdeltimeunit'    => 0,
                'oxupdateprice'    => '',
                'oxupdatepricea'   => 0,
                'oxupdatepriceb'   => 0,
                'oxupdatepricec'   => 0,
                'oxupdatepricetime' => '0000-00-00 00:00:00',
                'oxisdownloadable' => 0,
                'oxshowcustomagreement' => 1,
            ]);
            $oProduct->save();
        }

        return $productOxid;
    }
}
