<?php

namespace DPRMC\RemitSpiderUSBank\AsyncCollectors;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\Login;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\Objects\HistoryLink;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class HistoryLinks extends AbstractAsyncCollector {

    const HISTORY_LINK_PREFIX = '/TIR/public/deals/periodicReportHistory/';

    const DEAL_ID          = 'deal_id';
    const REPORT_NAME      = 'report_name';
    const MOST_RECENT_DATE = 'most_recent_date';
    const FILE_TYPES       = 'file_types_available';
    const HREF             = 'report_link';


    public function __construct( Login &$Login, Page &$Page, Debug &$Debug, string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        parent::__construct( $Login, $Page, $Debug, $timezone );
    }


    public function getHistoryLinks( string $dealLinkSuffix ): array {

        $this->_preAsyncCall( $dealLinkSuffix );

        $dealId = $this->_getDealIdFromDealLinkSuffix( $dealLinkSuffix );

        $asyncUrl2 = 'https://trustinvestorreporting.usbank.com/TIR/public/deals/periodicreport/' . $dealId . '/2';
        $html2     = $this->_getHtml( $asyncUrl2, $dealId );

        $arrayHistoryLinks2 = $this->_getHistoryLinkDataFromHtml( $html2, $dealId );

        $asyncUrl16 = 'https://trustinvestorreporting.usbank.com/TIR/public/deals/periodicreport/' . $dealId . '/16';
        $html16     = $this->_getHtml( $asyncUrl16, $dealId );

        $arrayHistoryLinks16 = $this->_getHistoryLinkDataFromHtml( $html16, $dealId );

        return array_merge( $arrayHistoryLinks2, $arrayHistoryLinks16 );
//
    }


    /**
     * @param string $html
     * @param int $tabSuffix
     * @return array
     */
    protected function _getHistoryLinkDataFromHtml( string $html = '', int $dealId = NULL ): array {

        // Not every Deal will have every tab. If I ask for a tab that doesn't exist for this
        // Deal then the HTML passed in will be empty.
        if ( empty( $html ) ):
            return [];
        endif;


        $historyLinkData = [];
        $dom             = new \DOMDocument();
        @$dom->loadHTML( $html );

        $trElements = $dom->getElementsByTagName( 'tr' );

        $this->Debug->_debug( "There are " . $trElements->count() . " trElements." );
        /**
         * @var \DOMElement $trElement
         */
        foreach ( $trElements as $i => $trElement ):
            $tdElements = $trElement->getElementsByTagName( 'td', );
            $numTds     = $tdElements->count();

            if ( 4 == $numTds ):
                $reportName     = trim( $tdElements->item( 0 )->textContent );
                $mostRecentDate = Carbon::parse( trim( $tdElements->item( 1 )->textContent ), $this->timezone );
                $fileTypeString = trim( $tdElements->item( 2 )->textContent );
                $fileTypes      = explode( ' ', $fileTypeString );
                $fileTypes      = array_map( 'trim', $fileTypes ); // Remove starting and ending whitespace.
                $fileTypes      = array_filter( $fileTypes );      // Remove empty array elements.
                $fileTypes      = array_values( $fileTypes );      // Reindex the keys at zero.

                /**
                 * @var \DOMElement $historyLinkElement
                 */
                $historyLinkElement = $tdElements->item( 3 );
                $anchorElements     = $historyLinkElement->getElementsByTagName( 'a' );

                /**
                 * @var \DOMElement $anchorElement
                 */
                $anchorElement = $anchorElements->item( 0 );
                $href          = str_replace( self::HISTORY_LINK_PREFIX, '', trim( $anchorElement->getAttribute( 'href' ) ) );

                $this->Debug->_debug( $reportName );
                $this->Debug->_debug( $mostRecentDate );
                $this->Debug->_debug( implode( ',', $fileTypes ) );
                $this->Debug->_debug( $href );

                $historyLinkData[] = [
                    self::DEAL_ID          => $dealId,
                    self::REPORT_NAME      => $reportName,
                    self::MOST_RECENT_DATE => $mostRecentDate,
                    self::FILE_TYPES       => $fileTypes,
                    self::HREF             => $href,
                ];
            else:
                $this->Debug->_debug( "$i: There are " . $numTds . " tdElements under this trElement, so skip this one." );
            endif;

        endforeach;

        return $historyLinkData;
    }

}
