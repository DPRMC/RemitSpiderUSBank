<?php

namespace DPRMC\RemitSpiderUSBank;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Clip;

class RemitSpiderUSBank {


    protected string                                        $chromePath;
    protected \HeadlessChromium\Browser\ProcessAwareBrowser $browser;
    protected string                                        $user;
    protected string                                        $pass;
    protected bool                                          $debug;
    protected string                                        $pathToScreenshots;

    protected \HeadlessChromium\Page $page;

    const URL_LOGIN = 'https://usbtrustgateway.usbank.com/portal/login.do';
    // const URL_LOGIN = 'https://fims2.deerparkrd.com/test-us';
    const URL_TEST = 'https://fims2.deerparkrd.com/test-us';

    const BROWSER_ENABLE_IMAGES    = TRUE;
    const BROWSER_CONNECTION_DELAY = 0.8; // How long between browser actions. More human-like?
//    const BROWSER_WINDOW_SIZE_WIDTH  = 500;
//    const BROWSER_WINDOW_SIZE_HEIGHT = 440;

    const BROWSER_WINDOW_SIZE_WIDTH  = 1000;
    const BROWSER_WINDOW_SIZE_HEIGHT = 1000;

//    const LOGIN_BUTTON_X = 40;
//    const LOGIN_BUTTON_Y = 260;

    const LOGIN_BUTTON_X = 80;
    const LOGIN_BUTTON_Y = 260;

    const NAV_APPLICATIONS_X = 50;
    const NAV_APPLICATIONS_Y = 125;


    const NAV_TRUST_INVESTOR_REPORTING_X = 150;
    const NAV_TRUST_INVESTOR_REPORTING_Y = 165;



    public function __construct( string $chromePath,
                                 string $user,
                                 string $pass,
                                 bool   $debug = FALSE,
                                 string $pathToScreenshots = '' ) {
        $this->chromePath        = $chromePath;
        $this->user              = $user;
        $this->pass              = $pass;
        $this->debug             = $debug;
        $this->pathToScreenshots = $pathToScreenshots;


        $browserFactory = new BrowserFactory( $this->chromePath );
        // starts headless chrome
        $this->browser = $browserFactory->createBrowser( [
                                                             'headless'        => TRUE,         // disable headless mode
                                                             'connectionDelay' => self::BROWSER_CONNECTION_DELAY,           // add 0.8 second of delay between each instruction sent to chrome,
                                                             //'debugLogger'     => 'php://stdout', // will enable verbose mode
                                                             'windowSize'      => [ self::BROWSER_WINDOW_SIZE_WIDTH,
                                                                                    self::BROWSER_WINDOW_SIZE_HEIGHT ],
                                                             'enableImages'    => self::BROWSER_ENABLE_IMAGES,
                                                         ] );

        // creates a new page and navigate to an url
        $this->page = $this->browser->createPage();
    }


    public function login(): bool {
        $this->page->navigate( self::URL_LOGIN )->waitForNavigation();
        ////die(self::URL_TEST);
        //$this->page->navigate( self::URL_TEST )->waitForNavigation();
        //$this->page->evaluate( "alert('foo');" );
        //sleep(1);
        //$this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_test_.jpg' );
        //die('done');

        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_first_page.jpg' );
        endif;


        $this->page->evaluate( "document.querySelector('#uname').value = '" . $this->user . "';" );
        $this->page->evaluate( "document.querySelector('#pword').value = '" . $this->pass . "';" );

        // DEBUG
        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_filled_in_user_pass.jpg' );
            $x      = 0;
            $y      = 0;
            $width  = self::LOGIN_BUTTON_X;
            $height = self::LOGIN_BUTTON_Y;
            $clip   = new Clip( $x, $y, $width, $height );

            // take the screenshot (in memory binaries)
            $this->page->screenshot( [
                                         'clip' => $clip,
                                     ] )
                       ->saveToFile( time() . '_' . microtime() . '_where_i_clicked_to_login.jpg' );
        endif;


        // Click the login button, and wait for the page to reload.
        $this->page->mouse()
                   ->move( self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y )
                   ->click();
        $this->page->waitForReload();


        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_am_i_logged_in.jpg' );
        endif;

        return TRUE;
    }


    public function goToTrustInvestorReporting() {

        // This forces the sub-menu to be visible.
        $this->page->evaluate( "document.querySelector('.sub_menu').style.visibility = 'visible';" );
        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_test_menu_should_be_visible.jpg' );
        endif;

        // DEBUG
//        if ( $this->debug ):
//            $x      = 0;
//            $y      = 0;
//            $width  = self::NAV_APPLICATIONS_X;
//            $height = self::NAV_APPLICATIONS_Y;
//            $clip   = new Clip( $x, $y, $width, $height );
//
//            $this->page->screenshot( [
//                                         'clip' => $clip,
//                                     ] )
//                       ->saveToFile( time() . '_' . microtime() . '_where_i_need_to_hover.jpg' );
//        endif;

//        $this->page->mouse()
//                   ->move( self::NAV_APPLICATIONS_X, self::NAV_APPLICATIONS_Y );
//
//        sleep( 1 );

        if ( $this->debug ):
            $x      = 0;
            $y      = 0;
            $width  = self::NAV_TRUST_INVESTOR_REPORTING_X;
            $height = self::NAV_TRUST_INVESTOR_REPORTING_Y;
            $clip   = new Clip( $x, $y, $width, $height );

            $this->page->screenshot( [
                                         'clip' => $clip,
                                     ] )
                       ->saveToFile( time() . '_' . microtime() . '_where_i_need_to_click_tir.jpg' );
        endif;


        // MOVE MOUSE METHOD
        $this->page->mouse()
                   ->move( self::NAV_TRUST_INVESTOR_REPORTING_X,
                           self::NAV_TRUST_INVESTOR_REPORTING_Y )
                   ->click();
        sleep(1);
        $this->page->mouse()
                   ->move( self::NAV_TRUST_INVESTOR_REPORTING_X,
                           self::NAV_TRUST_INVESTOR_REPORTING_Y )
                   ->click();
        sleep(1);
        $this->page->mouse()
                   ->move( self::NAV_TRUST_INVESTOR_REPORTING_X,
                           self::NAV_TRUST_INVESTOR_REPORTING_Y )
                   ->click();
        sleep(1);
        $this->page->mouse()
                   ->move( self::NAV_TRUST_INVESTOR_REPORTING_X,
                           self::NAV_TRUST_INVESTOR_REPORTING_Y )
                   ->click();
        //$this->page->waitForReload();

        echo "\n\nsleeping\n\n";
        flush();
        sleep(5);
        echo "\n\nawake\n\n";
        flush();

        // By this point I should be able to see the TIR interface.
        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_i_should_see_stuff.jpg' );
        endif;
    }


    public function goTo() {

    }


    /**
     * @param string $text
     *
     * @return bool
     */
    protected function isLoggedIn( string $text ): bool {
        $testString = 'Welcome,';
        if ( TRUE === str_contains( $text, $testString ) ):
            return TRUE;
        endif;
        return FALSE;
    }


}