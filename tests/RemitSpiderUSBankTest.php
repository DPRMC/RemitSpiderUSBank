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
                                                              '',
                                                              '',
                                                              '',
                                                              '',
                                                              '/Users/michaeldrennen/Desktop/files',
                                                              self::TIMEZONE );
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
//    public function testAll() {
//        $spider = $this->_getSpider();
//        $spider->Login->login();
//        $portfolioIds = $spider->Portfolios->getAll( $spider->Login->csrf );
//
//        $dealLinkSuffixesByPortfolioId = [];
//        foreach ( $portfolioIds as $portfolioId ):
//            $dealLinkSuffixesByPortfolioId[ $portfolioId ] = $spider->Deals->getAllByPortfolioId( $portfolioId );
//        endforeach;
//
//
//        $historyLinksByPortfolioId = [];
//        $dealIdToDealName          = [];
//        foreach ( $dealLinkSuffixesByPortfolioId as $portfolioId => $dealLinkSuffixes ):
//            $historyLinksByPortfolioId[$portfolioId] = [];
//            foreach ( $dealLinkSuffixes as $dealLinkSuffix ):
//                $historyLinks                         = $spider->HistoryLinks->getAllByDeal( $dealLinkSuffix );
//                $dealId                               = $spider->HistoryLinks->getDealId();
//                $dealName                             = $spider->HistoryLinks->getDealName();
//                $dealIdToDealName[ $dealId ]          = $dealName;
//                $historyLinksByPortfolioId[$portfolioId][ $dealId ] = $historyLinks;
//            endforeach;
//        endforeach;
//
//
//
//        $fileIndexes = [];
//        foreach ( $historyLinksByPortfolioId as $portfolioId => $dealIds ):
//            $fileIndexes[$portfolioId] = [];
//            foreach ( $dealIds as $dealId => $historyLinks ):
//                $fileIndexes[$portfolioId][$dealId] = [];
//                foreach($historyLinks as $historyLinkSuffix):
//                    $tempFileIndexes = $spider->FileIndex->getAllFromHistoryLink( $historyLinkSuffix);
//                    $fileIndexes[$portfolioId][$dealId] = array_merge($fileIndexes[$portfolioId][$dealId], $tempFileIndexes);
//                endforeach;
//            endforeach;
//        endforeach;
//
//
//        print_r($fileIndexes);
//    }


    /**
     * @test
     * @group errors
     */
    public function testIs404ShouldThrowException() {
        $this->expectException( \DPRMC\RemitSpiderUSBank\Exceptions\Exception404Returned::class );
        $url           = 'https://example.com/404.html';
        $pathTo404Html = 'tests/resources/404.html';
        $html          = file_get_contents( $pathTo404Html );
        \DPRMC\RemitSpiderUSBank\Helpers\Errors::is404( $url, $html );
    }


    /**
     * @test
     * @group login
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
        $this->assertNotEmpty( $spider->Portfolios->portfolioIds );

        // Calling this again, to test 'continue' code in _setDataToCache()
        $portfolios = $spider->Portfolios->getAll( $spider->Login->csrf );


        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\Portfolio $firstPortfolio
         */
        $firstPortfolio = $portfolios[ 0 ];

        $pulledInTheLastDay = $firstPortfolio->pulledInTheLastDay();
        $this->assertFalse( $pulledInTheLastDay );

        // Manually set the lastPulledAt time to an hour ago for the next assertion.
        $firstPortfolio->childrenLastPulled = \Carbon\Carbon::now( self::TIMEZONE )->subHour();

        $pulledInTheLastDay = $firstPortfolio->pulledInTheLastDay();
        $this->assertTrue( $pulledInTheLastDay );

//        $spider->Portfolios->deleteCache();
//        $this->assertFileDoesNotExist($spider->Portfolios->getPathToCache());


    }


    /**
     * @test
     * @group deals
     */
    public function testGetDealLinkSuffixes() {
        $spider = $this->_getSpider();
        $spider->Deals->deleteCache();
        $spider->Login->login();


        $deals = $spider->Deals->getAllByPortfolioId( $_ENV[ 'PORTFOLIO_ID' ],
                                                      $spider );
        $this->assertNotEmpty( $deals );

        $spider->Deals->loadFromCache();
        $this->assertNotEmpty( $spider->Deals->dealLinkSuffixes );

        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\Deal $firstDeal
         */
        $firstDeal = $deals[ 0 ];
        $this->assertInstanceOf( \DPRMC\RemitSpiderUSBank\Objects\Deal::class, $firstDeal );

        //$spider->Deals->notifyParentPullWasSuccessful($spider,$firstDeal->getDealId());

        $pulledInTheLastDay = $firstDeal->pulledInTheLastDay();
        $this->assertFalse( $pulledInTheLastDay );

        $firstDeal->childrenLastPulled = \Carbon\Carbon::now( self::TIMEZONE )->subHour();
        $pulledInTheLastDay            = $firstDeal->pulledInTheLastDay();
        $this->assertTrue( $pulledInTheLastDay );

//        $spider->Deals->deleteCache();
//        $this->assertFileDoesNotExist( $spider->Deals->getPathToCache() );
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
        $this->assertNotEmpty( $spider->HistoryLinks->historyLinks );

        $dealId   = $spider->HistoryLinks->getDealId();
        $dealName = $spider->HistoryLinks->getDealName();

        $this->assertIsString( $dealId );
        $this->assertIsString( $dealName );

        /**
         * @var array $historyLinksForPortfolioId An array of HistoryLink objects.
         */
        $historyLinksForPortfolioId = array_pop( $historyLinks );

        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\HistoryLink $firstHistoryLink
         */
        $firstHistoryLink = array_pop( $historyLinksForPortfolioId );
        $this->assertInstanceOf( \DPRMC\RemitSpiderUSBank\Objects\HistoryLink::class, $firstHistoryLink );

        $linkSuffix   = $firstHistoryLink->getLink();
        $absoluteLink = \DPRMC\RemitSpiderUSBank\Collectors\HistoryLinks::getAbsoluteLink( $linkSuffix );
        $this->assertIsString( $absoluteLink );

        $pulledInTheLastDay = $firstHistoryLink->pulledInTheLastDay();
        $this->assertFalse( $pulledInTheLastDay );

        $firstHistoryLink->childrenLastPulled = \Carbon\Carbon::now( self::TIMEZONE )->subHour();
        $pulledInTheLastDay                   = $firstHistoryLink->pulledInTheLastDay();
        $this->assertTrue( $pulledInTheLastDay );

//        $spider->HistoryLinks->deleteCache();
//        $this->assertFileDoesNotExist( $spider->HistoryLinks->getPathToCache() );
    }


    /**
     * @test
     * @group file
     */
    public function testGetFileIndexForDealShouldAddToIndex() {
        $spider = $this->_getSpider();
        $spider->Login->login();

        $spider->FileIndex->deleteCache();

        /**
         * @var array $fileIndexes
         */
        $fileIndexes = $spider->FileIndex->getAllFromHistoryLink( $_ENV[ 'HISTORY_LINK' ],
                                                                  $spider );

        print_r( $fileIndexes );

        $this->assertNotEmpty( $fileIndexes );
    }


    /**
     * @test
     * @group download
     */
    public function testGetFileNeedingApproveButtonClicked() {
        $spider = $this->_getSpider();
        $spider->Login->login();
        $spider->FileIndex->deleteCache();
        try {
            $response = $spider->FileIndex->getFileContentsViaPost( $spider, $_ENV[ 'FILE_LINK' ] );
        } catch (\Exception $exception) {
            $response = $spider->FileIndex->getFileContentsViaGet( $spider, $_ENV[ 'FILE_LINK' ] );
        }


        file_put_contents($response[\DPRMC\RemitSpiderUSBank\Collectors\FileIndex::FILENAME],
                          $response[\DPRMC\RemitSpiderUSBank\Collectors\FileIndex::BODY]);

        $this->assertNotEmpty($response[\DPRMC\RemitSpiderUSBank\Collectors\FileIndex::BODY]);

        $spider->FileIndex->markFileAsDownloaded($spider, ['1','2']);
    }


    /**
     * @test
     * @group 404
     */
    public function testGetFileThatDoesNotExistShouldCache404() {
        $spider = $this->_getSpider();
        $spider->Login->login();
        $spider->FileIndex->deleteCache();

        try {
            $response = $spider->FileIndex->getFileContentsViaGet( $spider, $_ENV[ 'FILE_LINK_404' ] );
        } catch (Exception $exception) {
            $spider->FileIndex->markFileAs404($spider, ['1','2']);
        }
    }







    /**
     * @test
     * @group pi
     */
    public function testGetPrincipalAndInterestFactors() {
        $spider = $this->_getSpider();
        $spider->Login->login();

        $path = '';
        $spider->PrincipalAndInterestFactors->downloadFilesByDealSuffix( $_ENV[ 'DEAL_SUFFIX' ], $path );

    }


}