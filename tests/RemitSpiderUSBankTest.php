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

    protected static bool $debug = FALSE;

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

        $this->assertTrue( $loggedIn );
    }


}