<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;

/**
 *
 */
class Login {

    const URL_LOGIN  = 'https://usbtrustgateway.usbank.com/portal/login.do';
    const URL_LOGOUT = RemitSpiderUSBank::BASE_URL . '/TIR/logout';

    const LOGIN_BUTTON_X = 80;
    const LOGIN_BUTTON_Y = 260;
    const URL_INTERFACE  = RemitSpiderUSBank::BASE_URL . '/TIR/portfolios';

    protected Page   $Page;
    protected Debug  $Debug;
    protected string $user;
    protected string $pass;

    public string $csrf;
    public CookiesCollection $cookies;


    /**
     * @param \HeadlessChromium\Page                 $Page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $Debug
     * @param string                                 $user
     * @param string                                 $pass
     */
    public function __construct( Page &$Page, Debug &$Debug, string $user, string $pass ) {
        $this->Page  = $Page;
        $this->Debug = $Debug;
        $this->user  = $user;
        $this->pass  = $pass;
    }

    /**
     * @return string
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
    public function login(): string {
        $this->Page->navigate( self::URL_LOGIN )->waitForNavigation();

        $this->Debug->_screenshot( 'first_page' );

        $this->Page->evaluate( "document.querySelector('#uname').value = '" . $this->user . "';" );
        $this->Page->evaluate( "document.querySelector('#pword').value = '" . $this->pass . "';" );

        // DEBUG
        $this->Debug->_screenshot( 'filled_in_user_pass' );
        $this->Debug->_screenshot( 'where_i_clicked_to_login', new Clip( 0, 0, self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y ) );


        // Click the login button, and wait for the page to reload.
        $this->Page->mouse()
                   ->move( self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y )
                   ->click();
        $this->Page->waitForReload();

        $this->Debug->_screenshot( 'am_i_logged_in' );

        $this->Page->navigate( self::URL_INTERFACE )->waitForNavigation( Page::NETWORK_IDLE, 5000 );

        $this->Debug->_screenshot( 'should_be_the_main_interface' );

        $this->cookies = $this->Page->getAllCookies();
        $postLoginHTML       = $this->Page->getHtml();
        $this->csrf          = $this->getCSRF( $postLoginHTML );
        return $postLoginHTML;
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
    public function logout(): bool {
        $this->Page->navigate( self::URL_LOGOUT )->waitForNavigation();
        $this->Debug->_screenshot( 'loggedout' );
        return TRUE;
    }


    /**
     * @param string $html
     *
     * @return string
     * @throws \Exception
     */
    protected function getCSRF( string $html ): string {
        $dom = new \DOMDocument();
        @$dom->loadHTML( $html );
        $inputs = $dom->getElementsByTagName( 'input' );
        foreach ( $inputs as $input ):
            $id = $input->getAttribute( 'id' );

            // This is the one we want!
            if ( 'OWASP_CSRFTOKEN' == $id ):
                return $input->getAttribute( 'value' );
            endif;
        endforeach;
        throw new \Exception( "Unable to find the CSRF value in the HTML." );
    }

}