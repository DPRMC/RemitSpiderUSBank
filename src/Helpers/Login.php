<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;


use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionIpHasBeenBlocked;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
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

    //const URL_INTERFACE  = RemitSpiderUSBank::BASE_URL . '/TIR/portfolios';
    // https://trustinvestorreporting.usbank.com/TIR/public/deals/
    const URL_INTERFACE = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals';

    protected Page   $Page;
    protected Debug  $Debug;
    protected string $user;
    protected string $pass;
    protected string $timezone;

    public ?string           $csrf = NULL;
    public CookiesCollection $cookies;


    /**
     * @param Page $Page
     * @param Debug $Debug
     * @param string $user
     * @param string $pass
     * @param string $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $user,
                                 string $pass,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page     = $Page;
        $this->Debug    = $Debug;
        $this->user     = $user;
        $this->pass     = $pass;
        $this->timezone = $timezone;
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
     * @throws \Exception
     * @throws ExceptionIpHasBeenBlocked
     */
    public function login(): string {
        $this->Debug->_debug( "Navigating to login screen." );
        $this->Page->navigate( self::URL_LOGIN )->waitForNavigation();

        $this->Debug->_screenshot( 'first_page' );
        $this->Debug->_html( 'first_page' );

        $testAccessDeniedHtml = $this->Page->getHtml();
        if ( str_contains( $testAccessDeniedHtml, 'Access Denied' ) ):
            throw new ExceptionIpHasBeenBlocked( "Access denied. HALT PROCESSING", 0, NULL, $testAccessDeniedHtml );
        endif;


        $this->Debug->_debug( "Filling out user and pass." );
        $this->Page->evaluate( "document.querySelector('#uname').value = '" . $this->user . "';" );
        $this->Page->evaluate( "document.querySelector('#pword').value = '" . $this->pass . "';" );

        // DEBUG
        $this->Debug->_screenshot( 'filled_in_user_pass' );
        $this->Debug->_screenshot( 'where_i_clicked_to_login', new Clip( 0, 0, self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y ) );


        // Click the login button, and wait for the page to reload.
        $this->Debug->_debug( "Clicking the login button." );
        $this->Page->mouse()
                   ->move( self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y )
                   ->click();
        $this->Page->waitForReload();

        $this->Debug->_screenshot( 'am_i_logged_in' );
        $this->Debug->_html( 'am_i_logged_in' );

        $currentUrl = $this->Page->getCurrentUrl();
        $this->Debug->_debug( "Currently at: " . $currentUrl );


        $this->Debug->_debug( "Navigating to the main interface at " . self::URL_INTERFACE );

        //$this->Page->navigate( self::URL_INTERFACE )->waitForNavigation( Page::NETWORK_IDLE, 5000 );
//        $this->Page->navigate( self::URL_INTERFACE )->waitForNavigation( Page::NETWORK_IDLE ); // It was timing out. And this code works.
        // I now need to click on the actual link. Instead of navigating directly to the interface.
        // 'TIR-Ext','https://trustinvestorreporting.usbank.com/TIR/portal/','TrustInvestorReporting
        //$openApplicationLink = RemitSpiderUSBank::BASE_URL . '/portal/public/openApplication.do?appName=TIR-Ext&appUrl=https://trustinvestorreporting.usbank.com/TIR/portal/';
        //$this->Page->navigate( $openApplicationLink )->waitForNavigation(Page::NETWORK_IDLE);

        $applicationsX = 90;
        $applicationsY = 130;

        $trustInvestorReportingX = 90;
        $trustInvestorReportingY = 164;
        $this->Page->mouse()->move( $applicationsX, $applicationsY );
        $this->Debug->_screenshot( 'first_mouse_move', new Clip( 0, 0, $applicationsX, $applicationsY ) );
        sleep( 1 );

        $this->Page->navigate( 'https://trustinvestorreporting.usbank.com/TIR/portal/' )->waitForNavigation( Page::NETWORK_IDLE );

//        $this->Page->evaluate(
//            "window.location='/portal/public/openApplication.do?appName=TIR-Ext&appUrl=https://trustinvestorreporting.usbank.com/TIR/portal/';"
//        );
//        // This loads a page with additional javascript.
//        sleep(4);

        $this->Debug->_screenshot( 'should_be_the_main_interface' );
        $this->Debug->_html( 'should_be_the_main_interface' );
        $this->cookies = $this->Page->getAllCookies();
        $postLoginHTML = $this->Page->getHtml();

        if ( USBankBrowser::isForbidden( $postLoginHTML ) ):
            throw new \Exception( "US Bank returned Forbidden: Access is denied", 403 );
        endif;

        $this->csrf = $this->getCSRF( $postLoginHTML );


        $this->Debug->_screenshot( "post_login" );
        $this->Debug->_html( "post_login" );
        $this->Debug->_debug( "CSRF saved to Login object: " . $this->csrf );
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

        // Secondary Search if first was unfruitful. I have been getting some errors.
        // This regex search is looing for:
        // xhr.setRequestHeader('OWASP_CSRFTOKEN', 'AAAA-BBBB-CCCC-DDDD-EEEE-FFFF-GGGG-HHHH');
        //$pattern = "/'OWASP_CSRFTOKEN', '(.*)'\);/";
        $pattern = '/([A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4})/';
        $matches = [];
        $success = preg_match( $pattern, $html, $matches );
        if ( 1 === $success ):
            return $matches[ 1 ];
        endif;

        throw new \Exception( "Unable to find the CSRF value in the HTML." );
    }

}