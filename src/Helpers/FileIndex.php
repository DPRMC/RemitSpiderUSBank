<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class FileIndex {


    protected Page   $Page;
    protected Debug  $Debug;
    protected string $pathToFileIndex;
    protected string $dealId;


//    /**
//     * Ex: https://trustinvestorreporting.usbank.com/TIR/public/deals/periodicReportHistory/1234/2/5678?extension=CSV
//     */
//    const BASE_FILE_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/periodicReportHistory/';


    /**
     * @param \HeadlessChromium\Page                 $Page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $Debug
     * @param string                                 $pathToFileIndex
     */
    public function __construct( Page &$Page, Debug &$Debug, string $pathToFileIndex ) {
        $this->Page            = $Page;
        $this->Debug           = $Debug;
        $this->pathToFileIndex = $pathToFileIndex;
    }


    /**
     * @param string $historyLinkSuffix
     *
     * @return void
     * @throws \Exception
     */
    protected function _setDealId( string $historyLinkSuffix ) {
        $historyLinkParts = explode( '/', $historyLinkSuffix );
        if ( count( $historyLinkParts ) < 3 ):
            throw new \Exception( "The string [" . $historyLinkSuffix . "] was not a valid History Link Suffix." );
        endif;

        $this->dealId = $historyLinkParts[ 0 ];
    }


    /**
     * @param string $historyLinkSuffix
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
    public function get( string $historyLinkSuffix ): array {

        $this->_setDealId( $historyLinkSuffix );
        $fileLinks = [];

        // Example URL:
        //
        $this->Page->navigate( HistoryLinks::HISTORY_LINK . $historyLinkSuffix )
                   ->waitForNavigation( Page::NETWORK_IDLE, 5000 );

        $this->Debug->_screenshot( 'historic_files_page_' . urlencode( $historyLinkSuffix ) );
        $this->Debug->_html( 'historic_files_page_' . urlencode( $historyLinkSuffix ) );

        $html = $this->Page->getHtml();
        $dom  = new \DOMDocument();
        @$dom->loadHTML( $html );


        $anchors = $dom->getElementsByTagName( 'a' );
        /**
         * @var \DOMElement $anchor
         */
        foreach ( $anchors as $anchor ):

            $href = $anchor->getAttribute( 'href' );
            if ( $this->_isFileLink( $href ) ):

                $fileTypeNode = $anchor->parentNode;
                $fileType     = trim( $fileTypeNode->nodeValue );


                /**
                 * @var \DOMText $reportDateNode
                 */
                $reportDateNode = $fileTypeNode->previousSibling;
                $reportDate     = Carbon::parse( trim( $reportDateNode->nodeValue ) );


                /**
                 * @var \DOMElement $garbageNode
                 */
                $garbageNode = $reportDateNode->previousSibling;


                /**
                 * @var \DOMElement $garbageNode
                 */
                $garbageNode = $garbageNode->previousSibling;


                /**
                 * @var \DOMElement $garbageNode
                 */
                $garbageNode = $garbageNode->previousSibling;
                $reportName  = trim( $garbageNode->nodeValue );


                /**
                 * @var \DOMElement $garbageNode
                 */
//                $garbageNode = $garbageNode->previousSibling;
//                var_dump(get_class($garbageNode)); flush();
//                var_dump(trim($garbageNode->nodeValue)); flush();

                var_dump( $fileType, $reportDate->toDateString(), $reportName );
                flush();

                die();

                print('$reportNameNode class: ');
                var_dump( get_class( $reportNameNode ) );
                flush();
                $reportName = trim( $reportNameNode->nodeValue );
                $tagName    = trim( $reportNameNode->tagName );
                print('tagname: ');
                var_dump( $tagName );
                print('$reportName value: ');
                var_dump( $reportName );

                $reportXNode = $reportNameNode->previousSibling;
                $reportX     = trim( $reportXNode->nodeValue );
                $tagName     = trim( $reportXNode->tagName );
                print('tagname');
                var_dump( $tagName );
                print('$reportX value');
                var_dump( $reportX );


                $reportXNode = $reportXNode->previousSibling;
                $reportX     = trim( $reportXNode->nodeValue );
                $tagName     = trim( $reportXNode->tagName );
                print('tagname');
                var_dump( $tagName );
                print('$reportX value');
                var_dump( $reportX );


                $reportXNode = $reportXNode->previousSibling;
                $reportX     = trim( $reportXNode->nodeValue );
                $tagName     = trim( $reportXNode->tagName );
                print('tagname');
                var_dump( $tagName );
                print('$reportX value');
                var_dump( $reportX );


                flush();
                die();

                $fileLinks[] = $href;
            endif;
        endforeach;

        return $fileLinks;
    }


    /**
     * @param array $newFileLinks
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheHistoryLinks( array $newFileLinks ): void {

        // Init the array if it does not exist.
        if ( file_exists( $this->pathToFileIndex ) ):
            $jsonFileLinks = file_get_contents( $this->pathToFileIndex );
            $fileLinks     = json_decode( $jsonFileLinks, TRUE );
        else:
            $fileLinks = [];
        endif;


        // Init the Security Index of the array if it does not exist.
        if ( FALSE == array_key_exists( $this->dealId, $fileLinks ) ):
            $fileLinks[ $this->dealId ] = [];
        endif;

        // Write all the new history links to the array.
        foreach ( $newHistoryLinks as $historyLink ):
            $myKey                                = $this->_getMyUniqueId( $historyLink );
            $fileLinks[ $this->dealId ][ $myKey ] = $historyLink;
        endforeach;


        // Encode and save the array to the json file.
        $writeSuccess = file_put_contents( $this->pathToHistoryLinks,
                                           json_encode( $fileLinks ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Deal History Links to cache file: " . $this->pathToHistoryLinks );
        endif;
    }


    /**
     * Helper function. This returns an MD5 hash used as a unique identifier (array index) for each file link.
     *
     * @param string $href
     *
     * @return string
     */
    protected function _getMyUniqueId( string $href ): string {
        return md5( $href );
    }


    /**
     * @param string $href
     *
     * @return bool
     */
    protected function _isFileLink( string $href ): bool {
        $prefix = '/TIR/public/deals/populateReportDocument/';
        if ( FALSE === stripos( $href, $prefix ) ):
            return FALSE;
        endif;

        return TRUE;
    }
}