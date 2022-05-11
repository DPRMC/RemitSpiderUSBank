<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Helpers\BaseData;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\Objects\File;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class FileIndex extends BaseData {


    protected string $dealId;

    // These are the indexes of the file index data.
    const TYPE = 'type';
    const DATE = 'date';
    const NAME = 'name';
    const HREF = 'href';
    const DEAL_ID = 'dealId';

//    /**
//     * Ex: https://trustinvestorreporting.usbank.com/TIR/public/deals/periodicReportHistory/1234/2/5678?extension=CSV
//     */
//    const BASE_FILE_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/periodicReportHistory/';


    /**
     * @param \HeadlessChromium\Page                 $Page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $Debug
     * @param string                                 $pathToFileIndex
     * @param string                                 $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToFileIndex,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page        = $Page;
        $this->Debug       = $Debug;
        $this->pathToCache = $pathToFileIndex;
        $this->timezone    = $timezone;
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
    public function getAllFromHistoryLink( string $historyLinkSuffix ): array {

        try {
            $this->Debug->_debug( "Getting all File Indexes from " . $historyLinkSuffix );
            $this->startTime = Carbon::now( $this->timezone );
            $this->loadFromCache();
            $this->_setDealId( $historyLinkSuffix );
            $fileLinks = [];

            // Example URL:
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
                $this->Debug->_debug( "href: " . $href );

                if ( $this->_isFileLink( $href ) ):

                    $fileTypeNode = $anchor->parentNode;
                    $fileType     = trim( $fileTypeNode->nodeValue );
                    $this->Debug->_debug( "fileType: " . $fileType );

                    /**
                     * @var \DOMText $reportDateNode
                     */
                    $garbageNode    = $fileTypeNode->previousSibling; // Skip a node
                    $reportDateNode = $garbageNode->previousSibling;
                    //$this->Debug->_debug("reportDateNode is a : " . get_class($reportDateNode));
                    //echo $reportDateNode->nodeValue;

                    $stringReportDate = isset( $reportDateNode->nodeValue ) ? trim( (string)$reportDateNode->nodeValue ) : NULL;
                    if ( $stringReportDate ):
                        $reportDate = Carbon::parse( trim( (string)$reportDateNode->nodeValue ), 'America/New_York' );
                        $this->Debug->_debug( "reportDate carbon : " . $reportDate->toDateString() );
                    else:
                        $reportDate = NULL;
                    endif;


                    /**
                     * @var \DOMElement $garbageNode
                     */
                    $garbageNode = $reportDateNode->previousSibling;


                    $reportNameNode = $garbageNode->previousSibling;
                    $reportName     = trim( $reportNameNode->nodeValue );
                    $this->Debug->_debug( "reportName: " . $reportName );

                    $uniqueId = $this->_getMyUniqueId( $href );

                    $fileLinks[ $uniqueId ] = [
                        self::TYPE => $fileType,
                        self::DATE => $reportDate->toDateString(),
                        self::NAME => $reportName,
                        self::HREF => $href,
                        self::DEAL_ID => $this->dealId,
                    ];
                endif;
            endforeach;

            $this->stopTime = Carbon::now( $this->timezone );

            $this->_setDataToCache( $fileLinks );
            $this->_cacheData();
            $this->Debug->_debug( "Writing the File Indexes to cache." );

            return $this->getObjects();
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
            $this->_cacheFailure( $exception );
            throw $exception;
        }
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
     * @param array $data
     *
     * @return void
     */
    protected function _setDataToCache( array $data ) {

        if ( FALSE == array_key_exists( $this->dealId, $this->data ) ):
            $this->data[ $this->dealId ] = [];
        endif;

        // Write all the new history links to the array.
        foreach ( $data as $newFileLinkData ):
            $myKey = $this->_getMyUniqueId( $newFileLinkData[ self::HREF ] );
            if ( FALSE == array_key_exists( $myKey, $this->data[ $this->dealId ] ) ):
                $newFileLinkData[ BaseData::LAST_PULLED ] = NULL;
                $newFileLinkData[ BaseData::ADDED_AT ]    = Carbon::now( $this->timezone );
                $this->data[ $this->dealId ][ $myKey ]    = $newFileLinkData;
            endif;
        endforeach;
    }


    /**
     * @return array
     */
    public function getObjects(): array {
        $objects = [];
        foreach ( $this->data as $data ):
            $objects[] = new File( $data, $this->timezone, $this->pathToCache, $this->dealId );
        endforeach;
        return $objects;
    }

    public function getDealId(): string {
        return $this->dealId;
    }
}