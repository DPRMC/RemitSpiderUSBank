<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;

use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;

/**
 *
 */
class Deals {

    protected Page  $Page;
    protected Debug $Debug;

    protected array  $dealLinkSuffixes;
    protected string $pathToDealLinkSuffixes;


    /**
     *
     */
    const URL_LIST_OF_DEALS = RemitSpiderUSBank::BASE_URL . '/TIR/portfolios/getPortfolioDeals/';

    const X_ALL = 220;
    const Y_ALL = 3915;


    /**
     * @param \HeadlessChromium\Page                 $Page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $Debug
     * @param string                                 $pathToDealLinkSuffixes
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToDealLinkSuffixes = '' ) {
        $this->Page                   = $Page;
        $this->Debug                  = $Debug;
        $this->pathToDealLinkSuffixes = $pathToDealLinkSuffixes;
    }


    /**
     * @param string $usBankPortfolioId
     *
     * @return array
     * @throws \DPRMC\RemitSpiderUSBank\Exceptions\Exception404Returned
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
     */
    public function getAllDealLinkSuffixesForPortfolioId( string $usBankPortfolioId ): array {

        // Start on Portfolios page
        $this->Page->navigate( Portfolios::URL_BASE_PORTFOLIOS )
                   ->waitForNavigation( Page::NETWORK_IDLE,
                                        USBankBrowser::NETWORK_IDLE_MS_TO_WAIT );

        $this->Debug->_screenshot( 'portfolios_page' . $usBankPortfolioId );
        $this->Debug->_html( 'portfolios_page_' . $usBankPortfolioId );

        // Ex:
        // https://trustinvestorreporting.usbank.com/TIR/portfolios/getPortfolioDeals/123456/0
        $linkToAllDealsInPortfolio = self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0';

        $this->Debug->_debug( "Navigating to Deal Links page at: " . $linkToAllDealsInPortfolio );

        $clip = new Clip( 0, 0, self::X_ALL, self::Y_ALL );
        $this->Debug->_screenshot( 'test', $clip );

        $this->Page->mouse()->move( self::X_ALL, self::Y_ALL )->click();

        sleep( 2 );

        $this->Debug->_debug( "We should be on the path with all Deals now." );

        $htmlWithListOfLinksToDeals = $this->Page->getHtml();
        Errors::is404( self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0', $htmlWithListOfLinksToDeals );
        $this->Debug->_debug( "Got the HTML that should contain deal links." );

        $this->Debug->_screenshot( 'all_deals_for_portfolioid_' . $usBankPortfolioId );
        $this->Debug->_html( 'all_deals_for_portfolioid_' . $usBankPortfolioId );
        $this->dealLinkSuffixes = $this->_parseDealLinkSuffixesFromHTML( $htmlWithListOfLinksToDeals );

        $this->Debug->_debug( "I found " . count( $this->dealLinkSuffixes ) . " Deal Link Suffixes." );

        $this->_cacheDealLinkSuffixes();

        $this->Debug->_debug( "Writing the Deal Link Suffixes to cache." );

        return $this->dealLinkSuffixes;
    }


    /**
     * @param string $htmlWithListOfLinksToDeals
     *
     * @return array
     * @throws \Exception
     */
    protected function _parseDealLinkSuffixesFromHTML( string $htmlWithListOfLinksToDeals ): array {
        $dealLinkSuffixes = [];
        $pattern = '/\/detail\/(\d*\/.*)/';
        $dom     = new \DOMDocument();
        @$dom->loadHTML( $htmlWithListOfLinksToDeals );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $id = $element->getAttribute( 'id' );

            // This is the one we want!
            if ( 'draggable-report-1' == $id ):
                $href            = $element->getAttribute( 'href' );
                $dealLinkSuffixe = NULL;
                preg_match( $pattern, $href, $dealLinkSuffixe );

                if ( 2 != count( $dealLinkSuffixe ) ):
                    throw new \Exception( "Unable to find link to deal in this string: " . $href );
                endif;

                $dealLinkSuffixes[] = $dealLinkSuffixe[ 1 ];
            endif;
        endforeach;
        return $dealLinkSuffixes;
    }


    /**
     * @return void
     * @throws \Exception
     */
    protected function _cacheDealLinkSuffixes(): void {
        $writeSuccess = file_put_contents( $this->pathToDealLinkSuffixes,
                                           implode( "\n", $this->dealLinkSuffixes ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Deal link suffixes to cache file: " . $this->pathToDealLinkSuffixes );
        endif;
    }
}