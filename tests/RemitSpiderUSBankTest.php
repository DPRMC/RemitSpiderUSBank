<?php

use JetBrains\PhpStorm\NoReturn;
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
     * @group t
     */
    public function testTest() {
        $spider = $this->_getSpider();

        $spider->USBankBrowser->page->addScriptTag( [
                                                        'url' => 'https://code.jquery.com/jquery-3.6.0.min.js',
                                                    ] )
                                    ->waitForResponse();

        $spider->USBankBrowser->page->navigate( 'http://michaeldrennen.com/' )
                                    ->waitForNavigation();

        $spider->USBankBrowser->page->addScriptTag( [
                                                        'url' => 'https://code.jquery.com/jquery-3.6.0.min.js',
                                                    ] )
                                    ->waitForResponse();

        $html = $spider->USBankBrowser->page->getHtml();


        $js = '$(document).find("header > div > h1").text();';

        $evaluation = $spider->USBankBrowser->page
            ->evaluate( $js );

        $returnType = $evaluation->getReturnType();

        $anchors = $evaluation->getReturnValue();

        var_dump( $anchors );
        flush();
        die();

        $anchors = $evaluation->getReturnValue();
        var_dump( $evaluation );
        flush();
        die();
        $anchors = $evaluation->getReturnValue();
        var_dump( $anchors );
        flush();
        die();
        foreach ( $anchors as $anchor ):
            var_dump( $anchor->href );
            flush();
        endforeach;
        die();
        //var_dump( $evaluation->getReturnValue() );
        flush();
        die();
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

//        $cookiesCollection = $spider->USBankBrowser->page->getCookies();
//        $cookieRows        = [];
//        /**
//         * @var \HeadlessChromium\Cookies\Cookie $cookie
//         */
//        foreach ( $cookiesCollection as $cookie ):
//            $cookieRows[ $cookie->getName() ] = $cookie->getName() . '=' . $cookie->getValue();
//        endforeach;
//        $cookieString = implode( '; ', $cookieRows );

        $cookieString = '';
        $dealLinkSuffixes = $spider->Deals->getAllDealLinkSuffixesForPortfolioId( $spider->Login->csrf,
                                                                                  $cookieString,
                                                                                  \DPRMC\RemitSpiderUSBank\Helpers\USBankBrowser::USER_AGENT_STRING,
                                                                                  $_ENV[ 'PORTFOLIO_ID' ] );

        var_dump( "These are deal link suffixes" );
        print_r( $dealLinkSuffixes );
        flush();
        die();
    }


//
//
//    /**
//     * @test
//     * @group globals
//     */
//    public function testGetAllPortfolioIdsShouldReturnAnArray() {
//        $portfolioIds = self::$spider->getAllPortfolioIds();
//        $this->assertGreaterThan( 0, count( $portfolioIds ) );
//    }
//
//
//    /**
//     * @test
//     * @group g
//     */
//    public function testGetAllDealIdsForPortfolioId() {
//        $dealIds = self::$spider->getAllDealLinkSuffixesForPortfolioId( $_ENV[ 'PORTFOLIO_ID' ] );
//        print_r( $dealIds );
//        flush();
//        die();
//        $this->assertGreaterThan( 0, count( $dealIds ) );
//    }
//
//
//    /**
//     * @test
//     */
//    public function testHolder() {
//
////        $loggedIn = self::$spider->login();
////        $this->assertTrue( $loggedIn );
////        $links = self::$spider->getAllDealLinks();
////        print_r($links);
//
//        self::$spider->getAllDocs();
//    }
//
//
//    /**
//     * @test
//     * @group page
//     */
////    public function testCreatePage(){
////
////            self::$spider->login();
//////        print_r(self::$spider->cookies);
//////        flush();
//////        self::$spider->createPage();
//////
////            print_r(self::$spider->cookies);
////            flush();
////
////            self::$spider->reloadCookies();
////            die();
////    }


}