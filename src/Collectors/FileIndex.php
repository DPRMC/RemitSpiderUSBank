<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;


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

    // These are the indexes of the file index data.
    const TYPE = 'type';
    const DATE = 'date';
    const NAME = 'name';
    const HREF = 'href';


//    /**
//     * Ex: https://trustinvestorreporting.usbank.com/TIR/public/deals/periodicReportHistory/1234/2/5678?extension=CSV
//     */
//    const BASE_FILE_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/periodicReportHistory/';


    /**
     * @param \HeadlessChromium\Page                    $Page
     * @param \DPRMC\RemitSpiderUSBank\Collectors\Debug $Debug
     * @param string                                    $pathToFileIndex
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
            $this->Debug->_debug("href: " . $href);

            if ( $this->_isFileLink( $href ) ):

                $fileTypeNode = $anchor->parentNode;
                $fileType     = trim( $fileTypeNode->nodeValue );
                $this->Debug->_debug("fileType: " . $fileType);

                /**
                 * @var \DOMText $reportDateNode
                 */
                $garbageNode = $fileTypeNode->previousSibling; // Skip a node
                $reportDateNode = $garbageNode->previousSibling;
                //$this->Debug->_debug("reportDateNode is a : " . get_class($reportDateNode));
                //echo $reportDateNode->nodeValue;

                $reportDate     = Carbon::parse( trim( (string)$reportDateNode->nodeValue ), 'America/New_York' );
                $this->Debug->_debug("reportDate carbon : " . $reportDate->toDateString());

                /**
                 * @var \DOMElement $garbageNode
                 */
                $garbageNode = $reportDateNode->previousSibling;


                $reportNameNode = $garbageNode->previousSibling;
                $reportName  = trim( $reportNameNode->nodeValue );
                $this->Debug->_debug("reportName: " . $reportName);

                $uniqueId = $this->_getMyUniqueId( $href );

                $fileLinks[$uniqueId] = [
                    self::TYPE => $fileType,
                    self::DATE => $reportDate->toDateString(),
                    self::NAME => $reportName,
                    self::HREF => $href
                ];
            endif;
        endforeach;

        $this->_cacheHistoryLinks($fileLinks);

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
        foreach ( $newFileLinks as $newFileLinkData ):
            $myKey                                = $this->_getMyUniqueId( $newFileLinkData[self::HREF] );
            $fileLinks[ $this->dealId ][ $myKey ] = $newFileLinkData;
        endforeach;


        // Encode and save the array to the json file.
        $writeSuccess = file_put_contents( $this->pathToFileIndex,
                                           json_encode( $fileLinks ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Deal File Links to cache file: " . $this->pathToFileIndex );
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
        $prefix = '/TIR/public/deals/';
        if ( FALSE === stripos( $href, $prefix ) ):
            return FALSE;
        endif;

        return TRUE;
    }


    /**
     * @param string $suffix
     *
     * @return string
     */
    public static function getLink(string $suffix): string {

    }
}