<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


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


    /**
     * Ex: https://trustinvestorreporting.usbank.com/TIR/public/deals/periodicReportHistory/1234/2/5678?extension=CSV
     */
    const BASE_FILE_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/periodicReportHistory/';


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
        $dom = new \DOMDocument();
        @$dom->loadHTML( $html );
        //$element = $dom->getElementById( 'deal-document-results-table' );

        $tableBody = $dom->getElementsByTagName( 'tbody' )->item(0);

        $tableBodyChildren = $tableBody->childNodes;

        print_r($tableBodyChildren);
        flush();
        die();

        $tableRows = $dom->getElementsByTagName( 'tr' );


        /**
         * @var \DOMElement $tr
         */
        foreach( $tableRows as $tr):
//            var_dump(get_class($tr)); flush(); die();
            $class = $tr->getAttribute( 'class' );

            if('head' == empty($class)):
                continue;
            endif;





            var_dump($class); flush();
        endforeach;

        die();

        var_dump($element); flush(); die();
        foreach ( $elements as $element ):
            $class = $element->getAttribute( 'class' );

            // This is the one we want!
            if ( 'periodic_report_2' == $class ):
                $historyLinks[] = $element->getAttribute( 'href' );
            endif;
        endforeach;
//
//        $this->_cacheHistoryLinks( $historyLinks );
//

        return $fileLinks;
    }


    /**
     * @param array $newHistoryLinks
     *
     * @return void
     * @throws \Exception
     */
//    protected function _cacheHistoryLinks( array $newHistoryLinks ): void {
//
//        // Init the array if it does not exist.
//        if ( file_exists( $this->pathToHistoryLinks ) ):
//            $jsonHistoryLinks = file_get_contents( $this->pathToHistoryLinks );
//            $historyLinks     = json_decode( $jsonHistoryLinks, TRUE );
//        else:
//            $historyLinks = [];
//        endif;
//
//
//        // Init the Security Index of the array if it does not exist.
//        if ( FALSE == array_key_exists( $this->dealId, $historyLinks ) ):
//            $historyLinks[ $this->dealId ] = [];
//        endif;
//
//        // Write all the new history links to the array.
//        foreach ( $newHistoryLinks as $historyLink ):
//            $myKey                                   = $this->_getMyUniqueId( $historyLink );
//            $historyLinks[ $this->dealId ][ $myKey ] = $historyLink;
//        endforeach;
//
//
//        // Encode and save the array to the json file.
//        $writeSuccess = file_put_contents( $this->pathToHistoryLinks,
//                                           json_encode( $historyLinks ) );
//        if ( FALSE === $writeSuccess ):
//            throw new \Exception( "Unable to write US Bank Deal History Links to cache file: " . $this->pathToHistoryLinks );
//        endif;
//    }


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
}