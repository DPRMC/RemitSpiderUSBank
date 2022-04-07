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

    public static function setUpBeforeClass(): void {
        self::$spider = new DPRMC\RemitSpiderUSBank\RemitSpiderUSBank( $_ENV[ 'CHROME_PATH' ],
                                                                       $_ENV[ 'USBANK_USER' ],
                                                                       $_ENV[ 'USBANK_PASS' ],
                                                                       $_ENV[ 'PATH_TO_IDS' ],
                                                                       self::$debug );
    }

    public static function tearDownAfterClass(): void {
        self::$spider->logout();
    }




    public function aTestTest() {
        $chromePath        = '';
        $user              = '';
        $pass              = '';
        $pathToIds         = '';
        $debug             = FALSE;
        $pathToScreenshots = '';
        $remitSpiderUSBank = new DPRMC\RemitSpiderUSBank\RemitSpiderUSBank( $chromePath,
                                                                            $user,
                                                                            $pass,
                                                                            $pathToIds,
                                                                            $debug,
                                                                            $pathToScreenshots );
    }


    /**
     * @test
     * @group globals
     */
    public function testGetAllPortfolioIdsShouldReturnAnArray() {
        $portfolioIds = self::$spider->getAllPortfolioIds();
        $this->assertGreaterThan( 0, count( $portfolioIds ) );
    }


    /**
     * @test
     * @group g
     */
    public function testGetAllDealIdsForPortfolioId() {
        $dealIds = self::$spider->getAllDealLinkSuffixesForPortfolioId( $_ENV[ 'PORTFOLIO_ID' ] );
        print_r( $dealIds );
        flush();
        die();
        $this->assertGreaterThan( 0, count( $dealIds ) );
    }


    /**
     * @test
     */
    public function testHolder() {

//        $loggedIn = self::$spider->login();
//        $this->assertTrue( $loggedIn );
//        $links = self::$spider->getAllDealLinks();
//        print_r($links);

        self::$spider->getAllDocs();
    }


    /**
     * @test
     * @group page
     */
//    public function testCreatePage(){
//
//            self::$spider->login();
////        print_r(self::$spider->cookies);
////        flush();
////        self::$spider->createPage();
////
//            print_r(self::$spider->cookies);
//            flush();
//
//            self::$spider->reloadCookies();
//            die();
//    }


}