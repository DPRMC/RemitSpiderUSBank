<?php

namespace DPRMC\RemitSpiderUSBank;

use DPRMC\RemitSpiderUSBank\Helpers\Deals;
use DPRMC\RemitSpiderUSBank\Helpers\Portfolios;
use DPRMC\RemitSpiderUSBank\Helpers\USBankBrowser;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\Helpers\HistoryLinks;
use DPRMC\RemitSpiderUSBank\Helpers\Login;
use HeadlessChromium\Cookies\CookiesCollection;



/**
 *
 */
class RemitSpiderUSBank {


    public USBankBrowser $USBankBrowser;
    public Debug         $Debug;
    public Login         $Login;
    public Portfolios    $Portfolios;
    public Deals         $Deals;
    public HistoryLinks  $HistoryLinks;


    protected bool   $debug;
    protected string $pathToScreenshots;

    protected string $pathToPortfolioIds;
    protected string $pathToDealLinkSuffixes;

    protected array $portfolioIds;
    protected array $dealIds;

    protected \HeadlessChromium\Page $page;


    const BASE_URL                    = 'https://trustinvestorreporting.usbank.com';
    const PORTFOLIO_IDS_FILENAME      = '_portfolio_ids.txt';
    const DEAL_LINK_SUFFIXES_FILENAME = '_deal_link_suffixes.txt';

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
                                 bool   $debug = FALSE,
                                 string $pathToScreenshots = '',
                                 string $pathToPortfolioIds = '',
                                 string $pathToDealLinkSuffixes = ''
    ) {

        $this->debug                  = $debug;
        $this->pathToScreenshots      = $pathToScreenshots;
        $this->pathToPortfolioIds     = $pathToPortfolioIds . self::PORTFOLIO_IDS_FILENAME;
        $this->pathToDealLinkSuffixes = $pathToDealLinkSuffixes . self::DEAL_LINK_SUFFIXES_FILENAME;

        $this->USBankBrowser = new USBankBrowser( $chromePath );

        $this->Debug         = new Debug( $this->USBankBrowser->page,
                                          $pathToScreenshots,
                                          $debug );

        $this->Login         = new Login( $this->USBankBrowser->page,
                                          $this->Debug,
                                          $user,
                                          $pass );

        $this->Portfolios = new Portfolios( $this->USBankBrowser->page,
                                            $this->Debug,
                                            $this->pathToPortfolioIds );

        $this->Deals = new Deals( $this->USBankBrowser->page,
                                  $this->Debug,
                                  $this->pathToDealLinkSuffixes );

        $this->HistoryLinks = new HistoryLinks( $this->USBankBrowser->page,
                                                $this->Debug );
    }




    /**
     *
     */
    private
    function _loadIds() {
        if ( file_exists( $this->pathToPortfolioIds ) ):
            $this->portfolioIds = file( $this->pathToPortfolioIds );
        else:
            file_put_contents( $this->pathToPortfolioIds, NULL );
        endif;

        if ( file_exists( $this->pathToDealLinkSuffixes ) ):
            $this->dealIds = file( $this->pathToDealLinkSuffixes );
        else:
            file_put_contents( $this->pathToDealLinkSuffixes, NULL );
        endif;
    }


    /*

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
//    public function getAllDealLinkSuffixesForPortfolioId( string $usBankPortfolioId ): array {
//        $postLoginHTML             = $this->login();
//        $linkToAllDealsInPortfolio = self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0?OWASP_CSRFTOKEN=' . $this->csrf;
//        try {
//            $this->page->navigate( $linkToAllDealsInPortfolio )->waitForNavigation( Page::NETWORK_IDLE,
//                                                                                    self::NETWORK_IDLE_MS_TO_WAIT );
//            $htmlWithListOfLinksToDeals = $this->page->getHtml();
//
//            $this->_screenshot( 'all_deals_for_portfolioid_' . $usBankPortfolioId );
//            $this->_html( 'all_deals_for_portfolioid_' . $usBankPortfolioId );
//            $dealIds = $this->_getDealLinkSuffixesFromHTML( $htmlWithListOfLinksToDeals );
//
//            $writeSuccess = file_put_contents( $this->pathToDealIds, implode( "\n", $dealIds ) );
//            if ( FALSE === $writeSuccess ):
//                throw new \Exception( "Unable to write US Bank Deal IDs to cache file: " . $this->pathToDealIds );
//            endif;
//
//            $this->logout();
//            return $dealIds;
//        } catch ( \Exception $exception ) {
//            $this->_debug( "    EXCEPTION: " . $exception->getMessage() );
//            $this->usBankPortfolioIdsMissingDealLinks[] = $usBankPortfolioId;
//            throw $exception;
//        }
//    }
//
//
//    public function getHistoryLinksFromDealPage( string $dealLinkSuffix ): array {
//        // Navigate to page
//
//        // Snag HTML
//
//        // Parse history links from HTML
//    }


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


//    protected function _getDealLinkSuffixesFromHTML( string $htmlWithListOfLinksToDeals ): array {
//        $listOfSecurityIds = [];
//        //$pattern     = '/\/detail\/(\d*)\//'; // This will grab the ID and Deal Name
//        $pattern = '/\/detail\/(\d*\/.*)/';
//        $dom     = new \DOMDocument();
//        @$dom->loadHTML( $htmlWithListOfLinksToDeals );
//        $elements = $dom->getElementsByTagName( 'a' );
//        foreach ( $elements as $element ):
//            $id = $element->getAttribute( 'id' );
//
//            // This is the one we want!
//            if ( 'draggable-report-1' == $id ):
//                $href        = $element->getAttribute( 'href' );
//                $securityIds = NULL;
//                preg_match( $pattern, $href, $securityIds );
//
//                if ( 2 != count( $securityIds ) ):
//                    throw new \Exception( "Unable to find link to deal in this string: " . $href );
//                endif;
//
//                $listOfSecurityIds[] = $securityIds[ 1 ];
//            endif;
//        endforeach;
//        return $listOfSecurityIds;
//    }


//    public function getAllDealLinks( string $postLoginHTML ): array {
//
//        $this->csrf               = $this->getCSRF( $postLoginHTML );
//        $this->usBankPortfolioIds = $this->_getUSBankPortfolioIds( $postLoginHTML );
//
//        $portfolioLinks = [];
//
//        foreach ( $this->usBankPortfolioIds as $usBankPortfolioId ):
//            $portfolioLinks[ $usBankPortfolioId ] = self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0?OWASP_CSRFTOKEN=' . $this->csrf;
//        endforeach;
//
//
//        // Example $link
//        // https://trustinvestorreporting.usbank.com/TIR/portfolios/getPortfolioDeals/123456/0?OWASP_CSRFTOKEN=1111-2222-3333-4444-5555-6666-7777-8888
//        foreach ( $portfolioLinks as $usBankPortfolioId => $portfolioLink ):
////            echo $portfolioLink; flush(); die($portfolioLink);
//            try {
//                $this->page->navigate( $portfolioLink )->waitForNavigation( Page::NETWORK_IDLE,
//                                                                            self::NETWORK_IDLE_MS_TO_WAIT );
//                $htmlWithListOfLinksToDeals = $this->page->getHtml();
//
//                $this->_screenshot( 'link_portfolioid_' . $usBankPortfolioId );
//                $this->setLinksToDealsBySecurityId( $htmlWithListOfLinksToDeals );
//            } catch ( \Exception $exception ) {
//                $this->_debug( "    EXCEPTION: " . $exception->getMessage() );
//                $this->usBankPortfolioIdsMissingDealLinks[] = $usBankPortfolioId;
//            }
//        endforeach;
//
//        if ( count( $this->linksToDealsBySecurityId ) < 1 ):
//            throw new \Exception( "No deals were found. Perhaps a login problem" );
//        endif;
//
//        return $this->linksToDealsBySecurityId;
//    }


//    public function getRecentDocs() {
//        $this->login();
//        $links = $this->getAllDealLinks();
//        foreach ( $links as $securityId => $link ):
//
//        endforeach;
//    }

//
//    // This will click on the history links to get all the files.
//    public function getAllDocs() {
//        $this->_debug( "Attempting to login to US Bank..." );
//        $this->login();
//        $this->_debug( "Logged in!" );
//
//        $this->_debug( "Getting all deal links" );
//        $html      = $this->page->getHtml();
//        $dealLinks = $this->getAllDealLinks( $html ); // This works.
//        $this->_debug( "I found " . count( $dealLinks ) . " deal links." );
//
//
//        foreach ( $dealLinks as $securityId => $dealLink ):
//            try {
//                $this->_debug( "Navigating to: " . $dealLink );
//
//                $this->page->navigate( $dealLink . '?OWASP_CSRFTOKEN=' . $this->csrf )
//                           ->waitForNavigation( Page::NETWORK_IDLE,
//                                                self::NETWORK_IDLE_MS_TO_WAIT );
//                $html = $this->page->getHtml();
//                $this->_debug( "I have the HTML code" );
//
//                $this->_screenshot( $securityId . '_main' );
//                $this->_html( $securityId . '_main' );
//
//                $this->historyLinksBySecurityId[ $securityId ] = $this->getHistoryLinks( $html );
//            } catch ( \Exception $exception ) {
//                $this->_debug( "    EXCEPTION: " . $exception->getMessage() );
//                $this->securityIdsMissingHistoryLinks[] = $securityId;
//            }
//
//        endforeach;
//
//
//        print_r( $this->historyLinksBySecurityId );
//        print_r( $this->securityIdsMissingHistoryLinks );
//        flush();
//        die();
//
//
//    }
//
//
//    private function getHistoryLinks( string $html ): array {
//        $historyLinks = [];
//        $dom          = new \DOMDocument();
//        @$dom->loadHTML( $html );
//        $elements = $dom->getElementsByTagName( 'a' );
//        foreach ( $elements as $element ):
//            $class = $element->getAttribute( 'class' );
//
//            // This is the one we want!
//            if ( 'periodic_report_2' == $class ):
//                $historyLinks[] = self::BASE_URL . $element->getAttribute( 'href' );
//            endif;
//        endforeach;
//        return $historyLinks;
//    }
//
//    private function getDownloadLinksFromHistoryLink( string $historyLink, int $securityId ): array {
//        $downloadLinks = [];
//
//        echo "Navigating to nhisoty link: " . $historyLink . "\n\n";
//        flush();
//
//        try {
//            $this->page->navigate( $historyLink . '&OWASP_CSRFTOKEN=' . $this->csrf )
//                       ->waitForNavigation( Page::NETWORK_IDLE,
//                                            self::NETWORK_IDLE_MS_TO_WAIT );
//            $html = $this->page->getHtml();
//            echo "Done navigating to history link\n";
//            flush();
//
//            $this->_screenshot( $securityId . '_history' );
//            $this->_html( $securityId . '_history' );
//
//            $downloadLinks = [];
//            $dom           = new \DOMDocument();
//            @$dom->loadHTML( $html );
//            $elements = $dom->getElementsByTagName( 'a' );
//
//            foreach ( $elements as $element ):
//                $href            = $element->getAttribute( 'href' );
//                $downloadLinks[] = $href;
//            endforeach;
//            echo "\nDone getting download links.\n";
//        } catch ( \Exception $exception ) {
//            echo "\n\nEXCEPTION: \n\n";
//            echo $exception->getMessage();
//            echo "\n\n\n";
//            flush();
//
//
//        }
//
//
//        return $downloadLinks;
//    }


}