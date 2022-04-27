<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class HistoryLinks {


    protected Page  $Page;
    protected Debug $Debug;


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


    /**
     * @param \HeadlessChromium\Page                 $Page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $Debug
     * @param string                                 $pathToHistoryLinks
     */
    public function __construct( Page &$Page, Debug &$Debug, string $pathToHistoryLinks ) {
        $this->Page               = $Page;
        $this->Debug              = $Debug;
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
    public function get( string $dealLinkSuffix ): array {

        $this->_setDealIdAndName( $dealLinkSuffix );
        $historyLinks = [];

        // Example URL:
        // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1234/abc-defg-2001-1
        $this->Page->navigate( self::BASE_DEAL_URL . $dealLinkSuffix )
                   ->waitForNavigation( Page::NETWORK_IDLE, 5000 );

        $this->Debug->_screenshot( 'deal_page_' . urlencode( $dealLinkSuffix ) );
        $this->Debug->_html( 'deal_page_' . urlencode( $dealLinkSuffix ) );

        $html = $this->Page->getHtml();

        $dom = new \DOMDocument();
        @$dom->loadHTML( $html );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $class = $element->getAttribute( 'class' );

            // This is the one we want!
            if ( 'periodic_report_2' == $class ):
                $fullSuffix = $element->getAttribute( 'href' );
//                var_dump($fullSuffix); flush();
//                var_dump(self::HISTORY_LINK_PREFIX); flush();
                $minSuffix      = str_replace( self::HISTORY_LINK_PREFIX, '', $fullSuffix );
                $historyLinks[] = $minSuffix;
            endif;
        endforeach;

        $this->_cacheHistoryLinks( $historyLinks );

        return $historyLinks;
    }


    /**
     * @param array $newHistoryLinks
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheHistoryLinks( array $newHistoryLinks ): void {

        // Init the array if it does not exist.
        if ( file_exists( $this->pathToHistoryLinks ) ):
            $jsonHistoryLinks = file_get_contents( $this->pathToHistoryLinks );
            $historyLinks     = json_decode( $jsonHistoryLinks, TRUE );
        else:
            $historyLinks = [];
        endif;


        // Init the Security Index of the array if it does not exist.
        if ( FALSE == array_key_exists( $this->dealId, $historyLinks ) ):
            $historyLinks[ $this->dealId ] = [];
        endif;

        // Write all the new history links to the array.
        foreach ( $newHistoryLinks as $historyLink ):
            $myKey                                   = $this->_getMyUniqueId( $historyLink );
            $historyLinks[ $this->dealId ][ $myKey ] = $historyLink;
        endforeach;


        // Encode and save the array to the json file.
        $writeSuccess = file_put_contents( $this->pathToHistoryLinks,
                                           json_encode( $historyLinks ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Deal History Links to cache file: " . $this->pathToHistoryLinks );
        endif;
    }


    /**
     * Helper function. This returns an MD5 hash used as a unique identifier (array index) for each history link.
     *
     * @param string $historyLink
     *
     * @return string
     */
    protected function _getMyUniqueId( string $historyLink ): string {
        return md5( $historyLink );
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
}