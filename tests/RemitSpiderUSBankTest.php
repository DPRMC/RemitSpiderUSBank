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
                                                                       self::$debug );
    }


    /**
     * @test
     * @group globals
     */
    public function testGetAllPortfolioIdsShouldReturnAnArray(){
        $portfolioIds = self::$spider->getAllPortfolioIds();
        $this->assertGreaterThan(0,count($portfolioIds));
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