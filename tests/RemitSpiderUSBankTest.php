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
        $portfolioIds = $spider->Portfolios->getAllPortfolioIds( $spider->Login->csrf );

        $dealLinkSuffixesByPortfolioId = [];
        foreach ( $portfolioIds as $portfolioId ):
            $dealLinkSuffixesByPortfolioId[ $portfolioId ] = $spider->Deals->getAllDealLinkSuffixesForPortfolioId( $portfolioId );
        endforeach;


        $historyLinksByPortfolioId = [];
        $dealIdToDealName          = [];
        foreach ( $dealLinkSuffixesByPortfolioId as $portfolioId => $dealLinkSuffixes ):
            $historyLinksByPortfolioId[$portfolioId] = [];
            foreach ( $dealLinkSuffixes as $dealLinkSuffix ):
                $historyLinks                         = $spider->HistoryLinks->get( $dealLinkSuffix );
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
                    $tempFileIndexes = $spider->FileIndex->get($historyLinkSuffix);
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
        $spider->Login->login();
        $spider->Portfolios->loadFromCache();
        $this->assertNotEmpty($spider->Portfolios->portfolioIds);
        $portfolioIds = $spider->Portfolios->getAllPortfolioIds( $spider->Login->csrf );
        $this->assertNotEmpty( $portfolioIds );
    }


    /**
     * @test
     * @group deals
     */
    public function testGetDealLinkSuffixes() {
        $spider = $this->_getSpider();
        $spider->Login->login();
        $dealLinkSuffixes = $spider->Deals->getAllDealLinkSuffixesForPortfolioId( $_ENV[ 'PORTFOLIO_ID' ] );
        $this->assertNotEmpty( $dealLinkSuffixes );
    }


    /**
     * @test
     * @group history
     */
    public function testGetGetHistoryLinks() {
        $spider = $this->_getSpider();
        $spider->Login->login();
        $historyLinks = $spider->HistoryLinks->get( $_ENV[ 'DEAL_SUFFIX' ] );

        //print_r($historyLinks); flush(); die();

        $this->assertNotEmpty( $historyLinks );
    }


    /**
     * @test
     * @group file
     */
    public function testGetFileIndexForDealShouldAddToIndex() {
        $spider = $this->_getSpider();
        $spider->Login->login();

        $fileIndex = $spider->FileIndex->get( $_ENV[ 'HISTORY_LINK' ] );
//        print_r($fileIndex); flush(); die();
    }

}