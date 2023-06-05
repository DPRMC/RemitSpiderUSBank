<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\Objects\HistoryLink;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;
use Illuminate\Support\Facades\Storage;

/**
 *
 */
class HistoryLinks extends BaseData {


    /**
     * @var string The file path to the json file that holds all History Links.
     */
    protected string $pathToHistoryLinks;


    /**
     * @var string Going with a string here in case a security id leads with a zero.
     */
    protected string $dealId;


    /**
     * @var string
     */
    protected string $dealName;

    /**
     * This exists in this->data as well, this property is available for clarity.
     *
     * @var array
     */
    public array $historyLinks;

    /**
     * Keyed by Deal ID, values are Carbon objects representing the most recent report date per Deal.
     *
     * @var array
     */
    public array $mostRecentReportDates = [];

    /**
     *
     */
    const BASE_DEAL_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/detail/';

    /**
     *
     */
    const HISTORY_LINK_PREFIX = '/TIR/public/deals/periodicReportHistory/';

    /**
     *
     */
    const HISTORY_LINK = RemitSpiderUSBank::BASE_URL . self::HISTORY_LINK_PREFIX;


    // CACHE
    const LINK         = 'link';       // The history link.
    const DEAL_ID      = 'dealId';
    const PRODUCT_TYPE = 'productType';


    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToHistoryLinks,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {

        parent::__construct( $Page, $Debug, $pathToHistoryLinks, $timezone );

        $this->pathToHistoryLinks = $pathToHistoryLinks;
    }


    /**
     * @param string $dealLinkSuffix
     *
     * @return void
     * @throws \Exception
     */
    protected function _setDealIdAndName( string $dealLinkSuffix ) {
        $dealLinkSuffixParts = explode( '/', $dealLinkSuffix );
        if ( count( $dealLinkSuffixParts ) < 2 ):
            throw new \Exception( "The string [" . $dealLinkSuffix . "] was not a valid Deal Link Suffix." );
        endif;

        $this->dealId   = $dealLinkSuffixParts[ 0 ];
        $this->dealName = $dealLinkSuffixParts[ 1 ];
    }


    /**
     * The parent method does the heavy lifting, I just denormalize the data for clarity.
     *
     * @return void
     */
    public function loadFromCache() {
        parent::loadFromCache();
        $this->historyLinks = $this->data;
    }


    /**
     * @param string $dealLinkSuffix
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
    public function getAllByDeal( string $dealLinkSuffix ): array {
        try {
            $this->Debug->_debug( "Getting all History Links for a Deal Link Suffix: " . $dealLinkSuffix );
            $this->_setDealIdAndName( $dealLinkSuffix );

            $this->loadFromCache();
            $this->startTime = Carbon::now( $this->timezone );

            $newHistoryLinks = [];

            // NEW
            $newHistoryLinkData = [];

            // Example URL:
            // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1234/abc-defg-2001-1 Not a good example.
            // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/11601/ubs-2012-c2 CMBS
            $this->Page->navigate( self::BASE_DEAL_URL . $dealLinkSuffix )
//                       ->waitForNavigation( Page::NETWORK_IDLE, 5000 );
                       ->waitForNavigation( Page::NETWORK_IDLE );

            $this->Debug->_screenshot( 'deal_page_' . urlencode( $dealLinkSuffix ) );
            $this->Debug->_html( 'deal_page_' . urlencode( $dealLinkSuffix ) );

            $html = $this->Page->getHtml();

            $productType = $this->_getProductTypeToDealRecord( $dealLinkSuffix, $html );


            $dom = new \DOMDocument();
            @$dom->loadHTML( $html );
            $elements = $dom->getElementsByTagName( 'a' );


            foreach ( $elements as $element ):
                $class = $element->getAttribute( 'class' );

                // This is the one we want!
                if ( 'periodic_report_2' == $class ):
                    $fullSuffix        = $element->getAttribute( 'href' );
                    $minSuffix         = str_replace( self::HISTORY_LINK_PREFIX, '', $fullSuffix );
                    $newHistoryLinks[] = $minSuffix;
                endif;
            endforeach;
            $this->Debug->_debug( "I found " . count( $newHistoryLinks ) . " History Links." );
            $this->stopTime = Carbon::now( $this->timezone );

            $mostRecentReportDate                         = $this->_getMostRecentReportDate( $dom );
            $this->mostRecentReportDates[ $this->dealId ] = $mostRecentReportDate;

            $this->_setDataToCache( $newHistoryLinks, $productType );

            $this->_cacheData();

//            return $newHistoryLinks;
            return $this->getObjects();
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
            $this->_cacheFailure( $exception );
            throw $exception;
        }

    }


    /**
     * @param array $data
     * @param string $productType
     * @return void
     */
    protected function _setDataToCache( array $data, string $productType = '' ) {
        // Init the Security Index of the array if it does not exist.
        if ( FALSE == array_key_exists( $this->dealId, $this->historyLinks ) ):
            $this->historyLinks[ $this->dealId ] = [];
        endif;

        // Write all the new history links to the array.
        foreach ( $data as $historyLink ):
            $myKey = $this->_getMyUniqueId( $historyLink );
            if (
                FALSE == array_key_exists( $myKey, $this->historyLinks[ $this->dealId ] )

            ):
                $this->historyLinks[ $this->dealId ][ $myKey ] = [
                    self::DEAL_ID                  => $this->dealId,
                    self::LINK                     => $historyLink,
                    self::PRODUCT_TYPE             => $productType,
                    BaseData::ADDED_AT             => Carbon::now( $this->timezone ),
                    BaseData::CHILDREN_LAST_PULLED => NULL,
                ];
            elseif ( sizeof( $this->historyLinks[ $this->dealId ][ $myKey ] ) < 4 ):
                $this->historyLinks[ $this->dealId ][ $myKey ] = [
                    self::DEAL_ID                  => $this->dealId,
                    self::LINK                     => $historyLink,
                    self::PRODUCT_TYPE             => $productType,
                    BaseData::ADDED_AT             => Carbon::now( $this->timezone ),
                    BaseData::CHILDREN_LAST_PULLED => NULL,
                ];
            else:
                // The value already exists. Do nothing.
            endif;


        endforeach;
        $this->data = $this->historyLinks;
    }


    /**
     * Simple getter.
     *
     * @return string
     */
    public function getDealId(): string {
        return $this->dealId;
    }


    /**
     * @return string
     */
    public function getDealName(): string {
        return $this->dealName;
    }


    /**
     * @param string $linkSuffix
     *
     * @return string
     */
    public static function getAbsoluteLink( string $linkSuffix ): string {
        return self::HISTORY_LINK . $linkSuffix;
    }

    public function getObjects(): array {
        $objects = [];

        foreach ( $this->data as $dealId => $historyLinks ):

            if ( empty( $historyLinks ) ):
                continue;
            endif;

            foreach ( $historyLinks as $uniqueId => $data ):
                $objects[ $dealId ][ $uniqueId ] = new HistoryLink( $data,
                                                                    $this->timezone,
                                                                    $this->pathToCache );
            endforeach;
        endforeach;

//        foreach ( $this->data as $portfolioId => $historyLinks ):
//            foreach ( $historyLinks as $uniqueId => $data ):
//                $objects[ $portfolioId ][ $uniqueId ] = new HistoryLink( $data,
//                                                                         $this->timezone,
//                                                                         $this->pathToCache,
//                                                                         $portfolioId );
//            endforeach;
//        endforeach;
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
        $spider->Deals->loadFromCache();
        $spider->Deals->data[ $parentId ][ BaseData::CHILDREN_LAST_PULLED ] = Carbon::now( $this->timezone );
        $spider->Deals->_cacheData();
    }


    /**
     * I am going to save this in the Deal record, so I can easily display Deals that have new data added to them.
     * @param \DOMDocument $dom
     *
     * @return \Carbon\Carbon|null
     */
    protected function _getMostRecentReportDate( \DOMDocument $dom ): ?Carbon {
        $mostRecentDate = Carbon::today()->addYear();
        $xpath          = new \DOMXPath( $dom );
//        $query          = "//table//tbody//tr//td[@width='20%']/text()";
//
//        $dateElements = $xpath->query( $query );
//        $dom->saveHTMLFile('deleteme.html');
//        /**
//         * @var \DOMNode $element
//         */
//        foreach ( $dateElements as $element ):
//            $stringDate = trim( $element->nodeValue );
//
//            $carbonDate = Carbon::parse( $stringDate );
//            if ( $carbonDate->lte( $mostRecentDate ) ):
//                $mostRecentDate = $carbonDate;
//            endif;
//        endforeach;
//
//        if ( $mostRecentDate->isFuture() ):
//            return NULL;
//        endif;
//
//        return $mostRecentDate;

        $elements = $dom->getElementsByTagName( 'label' );

        $labels = [];
        /**
         * @var \DOMElement $element
         */
        foreach ( $elements as $element ):
            //dump( trim( $element->textContent ) );
            $labels[] = trim( $element->textContent );
        endforeach;

        foreach ( $labels as $i => $label ):
            if ( 'Recent Report Date:' == $label ):
                break;
            endif;
        endforeach;

        $dateIndex = $i + 1;

        $mostRecentDate = Carbon::parse($labels[$dateIndex], $this->timezone);

        return $mostRecentDate;
    }


    /**
     * @param string $dealLinkSuffix
     * @param string $html
     * @return string
     */
    protected function _getProductTypeToDealRecord( string $dealLinkSuffix, string $html ): string {
        $dom = new \DOMDocument();
        @$dom->loadHTML( $html );
        /**
         * @var \DOMNodeList $elements
         */
        $elements    = $dom->getElementsByTagName( 'label' );
        $numElements = $elements->count();
        $productType = '';
        for ( $i = 0; $i < $numElements; $i++ ):
            $text = trim( $elements->item( $i )->textContent );
            if ( str_contains( $text, 'Product Type:' ) ):
                $productType = trim( $elements->item( $i + 1 )->textContent );
                break;
            endif;
        endfor;

        return $productType;
    }
}
