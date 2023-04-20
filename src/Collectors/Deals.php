<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\Helpers\Errors;
use DPRMC\RemitSpiderUSBank\Objects\Deal;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;


/**
 *
 */
class Deals extends BaseData {


    /**
     * Exists in this->data, but when loading from Cache I load it here for clarity.
     *
     * @var array
     */
    public array $dealLinkSuffixes;

    public string $portfolioId;

    /**
     *
     */
    const URL_LIST_OF_DEALS = RemitSpiderUSBank::BASE_URL . '/TIR/portfolios/getPortfolioDeals/';

    const X_ALL_DEFAULT = 220;
    const Y_ALL_DEFAULT = 3915;


    // CACHE
    const DEAL_LINK_SUFFIX = 'dealLinkSuffix';
    const PORTFOLIO_ID     = 'portfolioId';
    const DEAL_ID          = 'dealId';
    const DEAL_NAME        = 'dealName';

    protected int $x_all;
    protected int $y_all;

    /**
     * @param \HeadlessChromium\Page $Page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $Debug
     * @param string $pathToDealLinkSuffixes
     * @param string $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToDealLinkSuffixes = '',
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {

        parent::__construct( $Page, $Debug, $pathToDealLinkSuffixes, $timezone );
    }


    /**
     * @param string $usBankPortfolioId
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     * @param int $x_all Different versions of the Chromium browser have different fonts. Moves the links.
     * @param int $y_all
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
     */
    public function getAllByPortfolioId( string            $usBankPortfolioId,
                                         RemitSpiderUSBank $spider,
                                         int               $x_all = self::X_ALL_DEFAULT,
                                         int               $y_all = self::Y_ALL_DEFAULT ): array {

        $this->portfolioId = $usBankPortfolioId;

        try {
            $this->loadFromCache();
            $this->Debug->_debug( "Getting all Deal Link Suffixes." );
            $this->startTime = Carbon::now( $this->timezone );

            // Start on Portfolios page
            $this->Page->navigate( Portfolios::URL_BASE_PORTFOLIOS )
//                       ->waitForNavigation( Page::NETWORK_IDLE,USBankBrowser::NETWORK_IDLE_MS_TO_WAIT );
                       ->waitForNavigation(); // Was timing out, and this code works.

            $this->Debug->_screenshot( 'portfolios_page_' . $usBankPortfolioId );
            $this->Debug->_html( 'portfolios_page_' . $usBankPortfolioId );

            // Ex:
            // https://trustinvestorreporting.usbank.com/TIR/portfolios/getPortfolioDeals/123456/0
            $linkToAllDealsInPortfolio = self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0';

            $this->Debug->_debug( "Navigating to Deal Links page at: " . $linkToAllDealsInPortfolio );

            $clip = new Clip( 0, 0, $x_all, $y_all );
            $this->Debug->_screenshot( 'clickingOnAll', $clip );

            $this->Page->mouse()->move( $x_all, $y_all )->click();

            sleep( 2 );

            $this->Debug->_debug( "We should be on the path with all Deals now." );

            $htmlWithListOfLinksToDeals = $this->Page->getHtml();
            Errors::is404( self::URL_LIST_OF_DEALS . $usBankPortfolioId . '/0', $htmlWithListOfLinksToDeals );
            $this->Debug->_debug( "Got the HTML that should contain deal links." );

            $this->Debug->_screenshot( 'all_deals_for_portfolioid_' . $usBankPortfolioId, new Clip( 0, 0, 1000, 7000 ) );
            $this->Debug->_html( 'all_deals_for_portfolioid_' . $usBankPortfolioId );
            $this->dealLinkSuffixes = $this->_parseDealLinkSuffixesFromHTML( $htmlWithListOfLinksToDeals );

            $this->Debug->_debug( "I found " . count( $this->dealLinkSuffixes ) . " Deal Link Suffixes." );
            $this->stopTime = Carbon::now( $this->timezone );
            $this->_setDataToCache( $this->dealLinkSuffixes );
            $this->_cacheData();

            $this->notifyParentPullWasSuccessful( $spider, $usBankPortfolioId );

            $this->Debug->_debug( "Writing the Deal Link Suffixes to cache." );

            return $this->getObjects();
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
            $this->_cacheFailure( $exception );
            throw $exception;
        }

    }


    /**
     * @param string $htmlWithListOfLinksToDeals
     *
     * @return array
     * @throws \Exception
     */
    protected function _parseDealLinkSuffixesFromHTML( string $htmlWithListOfLinksToDeals ): array {
        $dealLinkSuffixes = [];
        $pattern          = '/\/detail\/(\d*\/.*)/';
        $dom              = new \DOMDocument();
        @$dom->loadHTML( $htmlWithListOfLinksToDeals );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $id = $element->getAttribute( 'id' );

            // This is the one we want!
            if ( 'draggable-report-1' == $id ):
                $href           = $element->getAttribute( 'href' );
                $dealLinkSuffix = NULL;
                preg_match( $pattern, $href, $dealLinkSuffix );

                if ( 2 != count( $dealLinkSuffix ) ):
                    throw new \Exception( "Unable to find link to deal in this string: " . $href );
                endif;

                $dealLinkSuffixes[] = $dealLinkSuffix[ 1 ];
            endif;
        endforeach;
        return $dealLinkSuffixes;
    }


    /**
     * @param array $data
     *
     * @return void
     * @throws \Exception
     */
    protected function _setDataToCache( array $data ) {
        $this->dealLinkSuffixes = $data;

        foreach ( $data as $dealLinkSuffix ):
            $dealId                = $this->_getDealIdFromSuffix( $dealLinkSuffix );
            $this->data[ $dealId ] = [
                BaseData::ADDED_AT             => Carbon::now( $this->timezone ),
                BaseData::CHILDREN_LAST_PULLED => NULL,
                self::DEAL_LINK_SUFFIX         => $dealLinkSuffix,
                self::PORTFOLIO_ID             => $this->portfolioId,
                self::DEAL_ID                  => $dealId,
                self::DEAL_NAME                => $this->_getDealNameFromSuffix( $dealLinkSuffix ),
            ];
        endforeach;
    }


    /**
     * @param string $dealSuffix
     *
     * @return string
     * @throws \Exception
     */
    protected function _getDealIdFromSuffix( string $dealSuffix ): string {
        $dealParts = $this->_getDealPartsFromSuffix( $dealSuffix );
        return $dealParts[ 0 ];
    }


    /**
     * @param string $dealSuffix
     *
     * @return string
     * @throws \Exception
     */
    protected function _getDealNameFromSuffix( string $dealSuffix ): string {
        $dealParts = $this->_getDealPartsFromSuffix( $dealSuffix );
        return $dealParts[ 1 ];
    }


    /**
     * @param string $dealSuffix
     *
     * @return array
     * @throws \Exception
     */
    private function _getDealPartsFromSuffix( string $dealSuffix ): array {
        $dealSuffixParts = explode( '/', $dealSuffix );
        if ( 2 != sizeof( $dealSuffixParts ) ):
            throw new \Exception( "Unable to find deal id and name in " . $dealSuffix );
        endif;

        return $dealSuffixParts;
    }


    /**
     * The parent method does the heavy lifting, I just denormalize the data for clarity.
     *
     * @return void
     */
    public function loadFromCache() {
        parent::loadFromCache();
        $this->dealLinkSuffixes = array_keys( $this->data );
    }


    /**
     * @return array
     */
    public function getObjects(): array {
        $objects = [];
        foreach ( $this->data as $data ):
            $objects[] = new Deal( $data,
                                   $this->timezone,
                                   $this->pathToCache );
        endforeach;
        return $objects;
    }

    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     * @param                                            $parentId
     *
     * @return void
     * @throws \Exception
     */
    public function notifyParentPullWasSuccessful( RemitSpiderUSBank $spider, $parentId ): void {
        $spider->Portfolios->loadFromCache();
        $spider->Portfolios->data[ $parentId ][ BaseData::CHILDREN_LAST_PULLED ] = Carbon::now( $this->timezone );
        $spider->Portfolios->_cacheData();
    }
}
