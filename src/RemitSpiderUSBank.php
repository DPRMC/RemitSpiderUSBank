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

    const URL_LOGIN     = 'https://usbtrustgateway.usbank.com/portal/login.do';
    const URL_INTERFACE = 'https://trustinvestorreporting.usbank.com/TIR/portfolios';

    const BROWSER_ENABLE_IMAGES    = TRUE;
    const BROWSER_CONNECTION_DELAY = 0.8; // How long between browser actions. More human-like?

    const BROWSER_WINDOW_SIZE_WIDTH  = 1000;
    const BROWSER_WINDOW_SIZE_HEIGHT = 1000;

    const LOGIN_BUTTON_X = 80;
    const LOGIN_BUTTON_Y = 260;


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


    /**
     * @return bool
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function login(): bool {
        $this->page->navigate( self::URL_LOGIN )->waitForNavigation();

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

        $this->page->navigate( self::URL_INTERFACE )->waitForNavigation();

        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_should_be_the_main_interface_.jpg' );
        endif;

        return TRUE;
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