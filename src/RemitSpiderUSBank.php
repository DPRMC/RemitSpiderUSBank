<?php

namespace DPRMC\RemitSpiderUSBank;

use DPRMC\RemitSpiderUSBank\Helpers\USBankBrowser;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\Helpers\HistoryLinks;
use DPRMC\RemitSpiderUSBank\Helpers\Login;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\CookiesCollection;
use HeadlessChromium\Page;


/**
 *
 */
class RemitSpiderUSBank {


    public USBankBrowser $USBankBrowser;
    public Debug         $Debug;
    public Login         $Login;
    public HistoryLinks  $HistoryLinks;


    protected string                                        $chromePath;
    protected \HeadlessChromium\Browser\ProcessAwareBrowser $browser;
    protected string                                        $user;
    protected string                                        $pass;
    protected bool                                          $debug;
    protected string                                        $pathToScreenshots;

    /**
     * @var string It's a great idea cache Portfolio IDs, Deal IDs, etc. I will store them in flat files at this path.
     */
    protected string $pathToIds;
    const PORTFOLIO_IDS_FILENAME = 'portfolio_ids.txt';
    const DEAL_IDS_FILENAME      = 'deal_ids.txt';

    protected string $pathToPortfolioIds;
    protected string $pathToDealIds;

    protected array $portfolioIds;
    protected array $dealIds;

    protected \HeadlessChromium\Page $page;

    const USER_AGENT_STRING = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.83 Safari/537.36';

    const URL_LOGIN         = 'https://usbtrustgateway.usbank.com/portal/login.do';
    const BASE_URL          = 'https://trustinvestorreporting.usbank.com';
    const URL_INTERFACE     = self::BASE_URL . '/TIR/portfolios';
    const URL_LIST_OF_DEALS = self::BASE_URL . '/TIR/portfolios/getPortfolioDeals/';
    const URL_LOGOUT        = self::BASE_URL . '/TIR/logout';

    const BROWSER_ENABLE_IMAGES    = TRUE;
    const BROWSER_CONNECTION_DELAY = 1; // How long between browser actions. More human-like?

    const BROWSER_WINDOW_SIZE_WIDTH  = 1000;
    const BROWSER_WINDOW_SIZE_HEIGHT = 1000;

    const LOGIN_BUTTON_X = 80;
    const LOGIN_BUTTON_Y = 260;

    const NETWORK_IDLE_MS_TO_WAIT = 4000;


    protected string $csrf;
    protected array  $usBankPortfolioIds = [];

    // There must have been some navigation error that prevented the deals page from loading.
    // Slap the portfolio ID into this array, and I can try to pull them down later.
    protected array $usBankPortfolioIdsMissingDealLinks = [];


    protected array $historyLinksBySecurityId       = [];
    protected array $securityIdsMissingHistoryLinks = [];


    /**
     * TESTING, not sure if this will work.
     *
     * @var CookiesCollection Saving the cookies post login. When the connection dies for no reason, I can restart the session.
     */
    public CookiesCollection $cookies;


    // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1710/abn-amro-2003-4
    protected array $linksToDealsBySecurityId = [];


    public function __construct( string $chromePath,
                                 string $user,
                                 string $pass,
                                 string $pathToIds,
                                 bool   $debug = FALSE,
                                 string $pathToScreenshots = '' ) {

        $this->USBankBrowser = new USBankBrowser( $chromePath );
        $this->Debug         = new Debug( $this->USBankBrowser->page,
                                          $pathToScreenshots,
                                          $debug );
        $this->Login         = new Login( $this->USBankBrowser->page,
                                          $this->Debug,
                                          $user,
                                          $pass );

        $this->HistoryLinks = new HistoryLinks( $this->USBankBrowser->page );
    }




//    public function __construct( string $chromePath,
//                                 string $user,
//                                 string $pass,
//                                 string $pathToIds,
//                                 bool   $debug = FALSE,
//                                 string $pathToScreenshots = '' ) {
//        $this->chromePath        = $chromePath;
//        $this->user              = $user;
//        $this->pass              = $pass;
//        $this->pathToIds         = $pathToIds;
//        $this->debug             = $debug;
//        $this->pathToScreenshots = $pathToScreenshots;
//
//        $this->cookies = new CookiesCollection();
//
//        $browserFactory = new BrowserFactory( $this->chromePath );
//        // starts headless chrome
//        $this->browser = $browserFactory->createBrowser( [
//                                                             'headless'        => TRUE,         // disable headless mode
//                                                             'connectionDelay' => self::BROWSER_CONNECTION_DELAY,           // add 0.8 second of delay between each instruction sent to chrome,
//                                                             //'debugLogger'     => 'php://stdout', // will enable verbose mode
//                                                             'windowSize'      => [ self::BROWSER_WINDOW_SIZE_WIDTH,
//                                                                                    self::BROWSER_WINDOW_SIZE_HEIGHT ],
//                                                             'enableImages'    => self::BROWSER_ENABLE_IMAGES,
//                                                             'customFlags'     => [ '--disable-web-security' ],
//                                                         ] );
//
//        $this->pathToPortfolioIds = $this->pathToIds . DIRECTORY_SEPARATOR . self::PORTFOLIO_IDS_FILENAME;
//        $this->pathToDealIds      = $this->pathToIds . DIRECTORY_SEPARATOR . self::DEAL_IDS_FILENAME;
//        $this->_loadIds();
//
//        // creates a new page and navigate to an url
//        $this->createPage();
//    }


    /**
     *
     */
    private function _loadIds() {
        if ( file_exists( $this->pathToPortfolioIds ) ):
            $this->portfolioIds = file( $this->pathToPortfolioIds );
        else:
            file_put_contents( $this->pathToPortfolioIds, NULL );
        endif;

        if ( file_exists( $this->pathToDealIds ) ):
            $this->dealIds = file( $this->pathToDealIds );
        else:
            file_put_contents( $this->pathToDealIds, NULL );
        endif;
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
        $this->page->navigate( self::URL_LOGIN )->waitForNavigation();

        $this->_screenshot( 'first_page' );

        $this->page->evaluate( "document.querySelector('#uname').value = '" . $this->user . "';" );
        $this->page->evaluate( "document.querySelector('#pword').value = '" . $this->pass . "';" );

        // DEBUG
        $this->_screenshot( 'filled_in_user_pass' );
        $this->_screenshot( 'where_i_clicked_to_login', new Clip( 0, 0, self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y ) );


        // Click the login button, and wait for the page to reload.
        $this->page->mouse()
                   ->move( self::LOGIN_BUTTON_X, self::LOGIN_BUTTON_Y )
                   ->click();
        $this->page->waitForReload();

        $this->_screenshot( 'am_i_logged_in' );

        $this->page->navigate( self::URL_INTERFACE )->waitForNavigation( Page::NETWORK_IDLE, 5000 );

        $this->_screenshot( 'should_be_the_main_interface' );

        $this->cookies = $this->page->getAllCookies();
        $postLoginHTML = $this->page->getHtml();
        $this->csrf    = $this->getCSRF( $postLoginHTML );
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
        $this->page->navigate( self::URL_LOGOUT )->waitForNavigation();
        $this->_screenshot( 'loggedout' );
        return TRUE;
    }


    /**
     * This method will return an array of US Bank Portfolio IDs.
     * Each of these Portfolio IDs will get passed to another method, that
     * will gather all the US Bank Deal IDs.
     *
     * @return array
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
    public function getAllPortfolioIds(): array {
        $postLoginHTML            = $this->login();
        $this->usBankPortfolioIds = $this->_getUSBankPortfolioIds( $postLoginHTML );

        $writeSuccess = file_put_contents( $this->pathToPortfolioIds, implode( "\n", $this->usBankPortfolioIds ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Portfolio IDs to cache file: " . $this->pathToPortfolioIds );
        endif;
        $this->logout();
        return $this->usBankPortfolioIds;
    }


    /**
     * @param string $usBankPortfolioId
     *
     * @return array
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
    public function getAllDealLinkSuffixesForPortfolioId( string $usBankPortfolioId ): array {
        $postLoginHTML             = $this->login();
        $linkToAllDealsInPortfolio = self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0?OWASP_CSRFTOKEN=' . $this->csrf;
        try {
            $this->page->navigate( $linkToAllDealsInPortfolio )->waitForNavigation( Page::NETWORK_IDLE,
                                                                                    self::NETWORK_IDLE_MS_TO_WAIT );
            $htmlWithListOfLinksToDeals = $this->page->getHtml();

            $this->_screenshot( 'all_deals_for_portfolioid_' . $usBankPortfolioId );
            $this->_html( 'all_deals_for_portfolioid_' . $usBankPortfolioId );
            $dealIds = $this->_getDealLinkSuffixesFromHTML( $htmlWithListOfLinksToDeals );

            $writeSuccess = file_put_contents( $this->pathToDealIds, implode( "\n", $dealIds ) );
            if ( FALSE === $writeSuccess ):
                throw new \Exception( "Unable to write US Bank Deal IDs to cache file: " . $this->pathToDealIds );
            endif;

            $this->logout();
            return $dealIds;
        } catch ( \Exception $exception ) {
            $this->_debug( "    EXCEPTION: " . $exception->getMessage() );
            $this->usBankPortfolioIdsMissingDealLinks[] = $usBankPortfolioId;
            throw $exception;
        }
    }


    public function getHistoryLinksFromDealPage( string $dealLinkSuffix ): array {
        // Navigate to page

        // Snag HTML

        // Parse history links from HTML
    }



    /**
     * This method REQUIRES that the User has already logged into the system.
     *
     * @param string $dealLinkSuffix
     *
     * @return array
     */
//    public function getFirstPageOfHistoryLinksFromDealLinkSuffix_WITHOUT_LOGIN(string $dealLinkSuffix): array {
//        $linkToAllDealsInPortfolio = self::BASE_URL . $usBankPortfolioId . '/0?OWASP_CSRFTOKEN=' . $this->csrf;
//        try {
//            $this->page->navigate( $linkToAllDealsInPortfolio )->waitForNavigation( Page::NETWORK_IDLE,
//                                                                                    self::NETWORK_IDLE_MS_TO_WAIT );
//    }


    /**
     * href example:
     * https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1710/abn-amro-2003-4
     * A Parser method that accepts HTML that should contain a list of Deals.
     * This method will parse that HTML and return an array of US Bank Deal IDs.
     *
     * @param string $htmlWithListOfLinksToDeals
     *
     * @return array
     * @throws \Exception
     */
    protected function _getDealLinkSuffixesFromHTML( string $htmlWithListOfLinksToDeals ): array {
        $listOfSecurityIds = [];
        //$pattern     = '/\/detail\/(\d*)\//'; // This will grab the ID and Deal Name
        $pattern = '/\/detail\/(\d*\/.*)/';
        $dom     = new \DOMDocument();
        @$dom->loadHTML( $htmlWithListOfLinksToDeals );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $id = $element->getAttribute( 'id' );

            // This is the one we want!
            if ( 'draggable-report-1' == $id ):
                $href        = $element->getAttribute( 'href' );
                $securityIds = NULL;
                preg_match( $pattern, $href, $securityIds );

                if ( 2 != count( $securityIds ) ):
                    throw new \Exception( "Unable to find link to deal in this string: " . $href );
                endif;

                $listOfSecurityIds[] = $securityIds[ 1 ];
            endif;
        endforeach;
        return $listOfSecurityIds;
    }


    /**
     * @param string $postLoginHTML This is the HTML of the page immediately following a successful login.
     *
     * @return array
     * @throws \Exception
     */
    public function getAllDealLinks( string $postLoginHTML ): array {

        $this->csrf               = $this->getCSRF( $postLoginHTML );
        $this->usBankPortfolioIds = $this->_getUSBankPortfolioIds( $postLoginHTML );

        $portfolioLinks = [];

        foreach ( $this->usBankPortfolioIds as $usBankPortfolioId ):
            $portfolioLinks[ $usBankPortfolioId ] = self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0?OWASP_CSRFTOKEN=' . $this->csrf;
        endforeach;


        // Example $link
        // https://trustinvestorreporting.usbank.com/TIR/portfolios/getPortfolioDeals/123456/0?OWASP_CSRFTOKEN=1111-2222-3333-4444-5555-6666-7777-8888
        foreach ( $portfolioLinks as $usBankPortfolioId => $portfolioLink ):
//            echo $portfolioLink; flush(); die($portfolioLink);
            try {
                $this->page->navigate( $portfolioLink )->waitForNavigation( Page::NETWORK_IDLE,
                                                                            self::NETWORK_IDLE_MS_TO_WAIT );
                $htmlWithListOfLinksToDeals = $this->page->getHtml();

                $this->_screenshot( 'link_portfolioid_' . $usBankPortfolioId );
                $this->setLinksToDealsBySecurityId( $htmlWithListOfLinksToDeals );
            } catch ( \Exception $exception ) {
                $this->_debug( "    EXCEPTION: " . $exception->getMessage() );
                $this->usBankPortfolioIdsMissingDealLinks[] = $usBankPortfolioId;
            }
        endforeach;

        if ( count( $this->linksToDealsBySecurityId ) < 1 ):
            throw new \Exception( "No deals were found. Perhaps a login problem" );
        endif;

        return $this->linksToDealsBySecurityId;
    }




//    public function getRecentDocs() {
//        $this->login();
//        $links = $this->getAllDealLinks();
//        foreach ( $links as $securityId => $link ):
//
//        endforeach;
//    }


    // This will click on the history links to get all the files.
    public function getAllDocs() {
        $this->_debug( "Attempting to login to US Bank..." );
        $this->login();
        $this->_debug( "Logged in!" );

        $this->_debug( "Getting all deal links" );
        $html      = $this->page->getHtml();
        $dealLinks = $this->getAllDealLinks( $html ); // This works.
        $this->_debug( "I found " . count( $dealLinks ) . " deal links." );


        foreach ( $dealLinks as $securityId => $dealLink ):
            try {
                $this->_debug( "Navigating to: " . $dealLink );

                $this->page->navigate( $dealLink . '?OWASP_CSRFTOKEN=' . $this->csrf )
                           ->waitForNavigation( Page::NETWORK_IDLE,
                                                self::NETWORK_IDLE_MS_TO_WAIT );
                $html = $this->page->getHtml();
                $this->_debug( "I have the HTML code" );

                $this->_screenshot( $securityId . '_main' );
                $this->_html( $securityId . '_main' );

                $this->historyLinksBySecurityId[ $securityId ] = $this->getHistoryLinks( $html );
            } catch ( \Exception $exception ) {
                $this->_debug( "    EXCEPTION: " . $exception->getMessage() );
                $this->securityIdsMissingHistoryLinks[] = $securityId;
            }

        endforeach;


        print_r( $this->historyLinksBySecurityId );
        print_r( $this->securityIdsMissingHistoryLinks );
        flush();
        die();

//        $this->_debug("I found " . count( $historyLinks ) . " history links.");
//
//
//        foreach ( $historyLinks as $historyLink ):
//            $downloadLinks = $this->getDownloadLinksFromHistoryLink( $historyLink, $securityId );
//            sleep( 1 );
//        endforeach;
//
//
//        print_r( $downloadLinks );
//        flush();
//        die();
    }


    /**
     * This appears to work
     *
     * @param string $html
     *
     * @return array
     */
    private function getHistoryLinks( string $html ): array {
        $historyLinks = [];
        $dom          = new \DOMDocument();
        @$dom->loadHTML( $html );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $class = $element->getAttribute( 'class' );

            // This is the one we want!
            if ( 'periodic_report_2' == $class ):
                $historyLinks[] = self::BASE_URL . $element->getAttribute( 'href' );
            endif;
        endforeach;
        return $historyLinks;
    }

    private function getDownloadLinksFromHistoryLink( string $historyLink, int $securityId ): array {
        $downloadLinks = [];

        echo "Navigating to nhisoty link: " . $historyLink . "\n\n";
        flush();

        try {
            $this->page->navigate( $historyLink . '&OWASP_CSRFTOKEN=' . $this->csrf )
                       ->waitForNavigation( Page::NETWORK_IDLE,
                                            self::NETWORK_IDLE_MS_TO_WAIT );
            $html = $this->page->getHtml();
            echo "Done navigating to history link\n";
            flush();

            $this->_screenshot( $securityId . '_history' );
            $this->_html( $securityId . '_history' );

            $downloadLinks = [];
            $dom           = new \DOMDocument();
            @$dom->loadHTML( $html );
            $elements = $dom->getElementsByTagName( 'a' );

            foreach ( $elements as $element ):
                $href            = $element->getAttribute( 'href' );
                $downloadLinks[] = $href;
            endforeach;
            echo "\nDone getting download links.\n";
        } catch ( \Exception $exception ) {
            echo "\n\nEXCEPTION: \n\n";
            echo $exception->getMessage();
            echo "\n\n\n";
            flush();


        }


        return $downloadLinks;
    }





    // Get all deal links
    // https://trustinvestorreporting.usbank.com/TIR/portfolios?layout=layout&OWASP_CSRFTOKEN=1111-2222-3333-4444-5555-6666-7777-8888#


    private function getPortfolioIdFromLink( string $portfolioLink ): string {

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


    /**
     * @param string $postLoginHTML
     *
     * @return array
     */
    protected function _getUSBankPortfolioIds( string $postLoginHTML ): array {
        $usBankPortfolioIds = [];
        $dom                = new \DOMDocument();
        @$dom->loadHTML( $postLoginHTML );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $class = $element->getAttribute( 'class' );

            // This is the one we want!
            if ( 'lnk_portfoliotab' == $class ):
                $usBankPortfolioIds[] = $element->getAttribute( 'id' );
            endif;
        endforeach;
        return $usBankPortfolioIds;
    }


    /**
     * Links look like this:
     * Ex: /TIR/public/deals/detail/5155/surf-2006-bc3
     *
     * @param string $html
     *
     * @return void
     * @throws \Exception
     */
    protected function setLinksToDealsBySecurityId( string $html ) {
        $pattern = '/\/detail\/(\d*)\//';
        $dom     = new \DOMDocument();
        @$dom->loadHTML( $html );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $securityIds = [];
            $id          = $element->getAttribute( 'id' );

            // This is the one we want!
            if ( 'draggable-report-1' == $id ):
                $href = $element->getAttribute( 'href' );
                preg_match( $pattern, $href, $securityIds );

                if ( 2 != count( $securityIds ) ):
                    throw new \Exception( "Unable to find link to deal in this string: " . $href );
                endif;

                $securityId                                    = $securityIds[ 1 ];
                $this->linksToDealsBySecurityId[ $securityId ] = self::BASE_URL . $href;
            endif;
        endforeach;
    }

    private function createPage() {
        $this->page = $this->browser->createPage();
        $this->page->setUserAgent( self::USER_AGENT_STRING );
        $this->page->setCookies( $this->cookies );
    }


    public function reloadCookies() {
        $this->page->setCookies( $this->cookies );
    }


    /**
     * This is just a little helper function to clean up some of the debug code.
     *
     * @param string                      $suffix
     * @param \HeadlessChromium\Clip|NULL $clip
     *
     * @return void
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function _screenshot( string $suffix, Clip $clip = NULL ) {
        if ( $this->debug ):
            if ( $clip ):
                $this->page->screenshot( [ 'clip' => $clip ] )->saveToFile( time() . '_' . microtime() . '_' . $suffix . '.jpg' );
            else:
                $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_' . $suffix . '.jpg' );
            endif;
        endif;
    }


    private function _html( string $filename ) {
        if ( $this->debug ):
            $html = $this->page->getHtml();
            file_put_contents( $this->pathToScreenshots . time() . '_' . microtime() . '_' . $filename . '.html', $html );
        endif;
    }

    private function _debug( string $message, bool $die = FALSE ) {
        if ( $this->debug ):
            echo "\n" . $message . "\n";
            flush();
            if ( $die ):
                die();
            endif;
        endif;
    }


}