<?php

use PHPUnit\Framework\TestCase;


/**
 * To run tests call:
 * php ./vendor/phpunit/phpunit/phpunit --group=first
 * Class BusinessDateTest
 */
class RemitSpiderUSBankTest extends TestCase {

    protected static \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider;

    protected static bool $debug = true;

    public static function setUpBeforeClass(): void {
        self::$spider = new DPRMC\RemitSpiderUSBank\RemitSpiderUSBank( $_ENV[ 'CHROME_PATH' ],
                                                                       $_ENV[ 'USBANK_USER' ],
                                                                       $_ENV[ 'USBANK_PASS' ],
                                                                       self::$debug );
    }


    /**
     * @test
     */
    public function testHolder() {

        $loggedIn = self::$spider->login();

        self::$spider->goTo();
        $this->assertTrue( $loggedIn );
    }


}