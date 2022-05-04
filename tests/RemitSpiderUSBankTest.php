<?php

use PHPUnit\Framework\TestCase;
use \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;

/**
 * To run tests call:
 * php ./vendor/phpunit/phpunit/phpunit --group=first
 * Class BusinessDateTest
 */
class RemitSpiderUSBankTest extends TestCase {

    protected static RemitSpiderUSBank $spider;

    protected static bool $debug = TRUE;

    const TIMEZONE = 'America/New_York';

    private function _getSpider(): RemitSpiderUSBank {
        return new DPRMC\RemitSpiderUSBank\RemitSpiderUSBank( $_ENV[ 'CHROME_PATH' ],
                                                              $_ENV[ 'USBANK_USER' ],
                                                              $_ENV[ 'USBANK_PASS' ],
                                                              self::$debug,
                                                              '',
                                                              '',
                                                              '' );
    }

    public static function setUpBeforeClass(): void {

    }


    public static function tearDownAfterClass(): void {

    }


    /**
     * @test
     */
    public function testConstructor() {
        $spider = $this->_getSpider();
        $this->assertInstanceOf( \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank::class,
                                 $spider );
    }


    /**
     * @test
     * @group all
     */
    public function testAll() {
        $spider = $this->_getSpider();
        $spider->Login->login();
        $portfolioIds = $spider->Portfolios->getAll( $spider->Login->csrf );

        $dealLinkSuffixesByPortfolioId = [];
        foreach ( $portfolioIds as $portfolioId ):
            $dealLinkSuffixesByPortfolioId[ $portfolioId ] = $spider->Deals->getAllByPortfolioId( $portfolioId );
        endforeach;


        $historyLinksByPortfolioId = [];
        $dealIdToDealName          = [];
        foreach ( $dealLinkSuffixesByPortfolioId as $portfolioId => $dealLinkSuffixes ):
            $historyLinksByPortfolioId[$portfolioId] = [];
            foreach ( $dealLinkSuffixes as $dealLinkSuffix ):
                $historyLinks                         = $spider->HistoryLinks->getAllByDeal( $dealLinkSuffix );
                $dealId                               = $spider->HistoryLinks->getDealId();
                $dealName                             = $spider->HistoryLinks->getDealName();
                $dealIdToDealName[ $dealId ]          = $dealName;
                $historyLinksByPortfolioId[$portfolioId][ $dealId ] = $historyLinks;
            endforeach;
        endforeach;



        $fileIndexes = [];
        foreach ( $historyLinksByPortfolioId as $portfolioId => $dealIds ):
            $fileIndexes[$portfolioId] = [];
            foreach ( $dealIds as $dealId => $historyLinks ):
                $fileIndexes[$portfolioId][$dealId] = [];
                foreach($historyLinks as $historyLinkSuffix):
                    $tempFileIndexes = $spider->FileIndex->getAllFromHistoryLink( $historyLinkSuffix);
                    $fileIndexes[$portfolioId][$dealId] = array_merge($fileIndexes[$portfolioId][$dealId], $tempFileIndexes);
                endforeach;
            endforeach;
        endforeach;


        print_r($fileIndexes);
    }


    /**
     * @test
     * @group errors
     */
    public function testIs404ShouldThrowException() {
        $this->expectException( \DPRMC\RemitSpiderUSBank\Exceptions\Exception404Returned::class );
        $url           = 'https://example.com/404.html';
        $pathTo404Html = 'tests/resources/404.html';
        $html          = file_get_contents( $pathTo404Html );
        \DPRMC\RemitSpiderUSBank\Collectors\Errors::is404( $url, $html );
    }


    /**
     * @test
     */
    public function testLoginAndLogout() {
        $spider        = $this->_getSpider();
        $postLoginHtml = $spider->Login->login();
        $this->assertIsString( $postLoginHtml );
        $loggedOut = $spider->Login->logout();
        $this->assertTrue( $loggedOut );
    }


    /**
     * @test
     * @group portfolios
     */
    public function testGetPortfolioIds() {
        $spider = $this->_getSpider();
        $spider->Portfolios->deleteCache();
        $spider->Login->login();

        $portfolios = $spider->Portfolios->getAll( $spider->Login->csrf );
        $this->assertNotEmpty( $portfolios );

        $spider->Portfolios->loadFromCache();
        $this->assertNotEmpty($spider->Portfolios->portfolioIds);

        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\Portfolio $firstPortfolio
         */
        $firstPortfolio = $portfolios[0];
        $pulledInTheLastDay = $firstPortfolio->pulledInTheLastDay();
        $this->assertTrue($pulledInTheLastDay);

        $firstPortfolio->lastPulledAt = \Carbon\Carbon::now(self::TIMEZONE)->subYear();
        $pulledInTheLastDay = $firstPortfolio->pulledInTheLastDay();
        $this->assertFalse($pulledInTheLastDay);

        $spider->Portfolios->deleteCache();
        $this->assertFileDoesNotExist($spider->Portfolios->getPathToCache());




    }


    /**
     * @test
     * @group deals
     */
    public function testGetDealLinkSuffixes() {
        $spider = $this->_getSpider();
        $spider->Deals->deleteCache();
        $spider->Login->login();
        $deals = $spider->Deals->getAllByPortfolioId( $_ENV[ 'PORTFOLIO_ID' ] );
        $this->assertNotEmpty( $deals );

        $spider->Deals->loadFromCache();
        $this->assertNotEmpty($spider->Deals->dealLinkSuffixes);

        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\Deal $firstDeal
         */
        $firstDeal = $deals[0];
        $this->assertInstanceOf(\DPRMC\RemitSpiderUSBank\Objects\Deal::class, $firstDeal);

        $pulledInTheLastDay = $firstDeal->pulledInTheLastDay();
        $this->assertTrue($pulledInTheLastDay);

        $firstDeal->lastPulledAt = \Carbon\Carbon::now(self::TIMEZONE)->subYear();
        $pulledInTheLastDay = $firstDeal->pulledInTheLastDay();
        $this->assertFalse($pulledInTheLastDay);

        $spider->Deals->deleteCache();
        $this->assertFileDoesNotExist($spider->Deals->getPathToCache());
    }


    /**
     * @test
     * @group history
     */
    public function testGetGetHistoryLinks() {
        $spider = $this->_getSpider();
        $spider->HistoryLinks->deleteCache();
        $spider->Login->login();

        $historyLinks = $spider->HistoryLinks->getAllByDeal( $_ENV[ 'DEAL_SUFFIX' ] );
        $this->assertNotEmpty( $historyLinks );

        $spider->HistoryLinks->loadFromCache();
        $this->assertNotEmpty($spider->HistoryLinks->historyLinks);

        $dealId = $spider->HistoryLinks->getDealId();
        $dealName = $spider->HistoryLinks->getDealName();

        $this->assertIsString($dealId);
        $this->assertIsString($dealName);

        /**
         * @var array $historyLinksForPortfolioId An array of HistoryLink objects.
         */
        $historyLinksForPortfolioId = array_pop($historyLinks);

        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\HistoryLink $firstHistoryLink
         */
        $firstHistoryLink = array_pop($historyLinksForPortfolioId);
        $this->assertInstanceOf(\DPRMC\RemitSpiderUSBank\Objects\HistoryLink::class, $firstHistoryLink);

        $linkSuffix = $firstHistoryLink->getLink();
        $absoluteLink = \DPRMC\RemitSpiderUSBank\Collectors\HistoryLinks::getAbsoluteLink($linkSuffix);
        $this->assertIsString($absoluteLink);

        $pulledInTheLastDay = $firstHistoryLink->pulledInTheLastDay();
        $this->assertTrue($pulledInTheLastDay);

        $firstHistoryLink->lastPulledAt = \Carbon\Carbon::now(self::TIMEZONE)->subYear();
        $pulledInTheLastDay = $firstHistoryLink->pulledInTheLastDay();
        $this->assertFalse($pulledInTheLastDay);

        $spider->HistoryLinks->deleteCache();
        $this->assertFileDoesNotExist($spider->HistoryLinks->getPathToCache());
    }


    /**
     * @test
     * @group file
     */
    public function testGetFileIndexForDealShouldAddToIndex() {
        $spider = $this->_getSpider();
        $spider->Login->login();

        $fileIndex = $spider->FileIndex->getAllFromHistoryLink( $_ENV[ 'HISTORY_LINK' ] );
        print_r($fileIndex); flush(); die();
    }

}