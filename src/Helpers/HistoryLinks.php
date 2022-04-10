<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBankBAK;
use HeadlessChromium\Page;

/**
 *
 */
class HistoryLinks {


    protected Page   $Page;
    protected Debug  $Debug;

    const BASE_DEAL_URL = RemitSpiderUSBankBAK::BASE_URL . '/TIR/public/deals/detail/';

    public function __construct( Page &$Page, Debug &$Debug, ) {
        $this->Page  = $Page;
        $this->Debug = $Debug;
    }



    public function get( string $dealLinkPrefix ): array {

        $historyLinks = [];

        // Example URL:
        // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1234/abc-defg-2001-1
        $this->Page->navigate( self::BASE_DEAL_URL . $dealLinkPrefix )
                   ->waitForNavigation( Page::NETWORK_IDLE, 5000 );

        $this->Debug->_screenshot( 'deal_page_' . urlencode($dealLinkPrefix) );
        $this->Debug->_html( 'deal_page_' . urlencode($dealLinkPrefix) );

        $html = $this->Page->getHtml();

        $dom          = new \DOMDocument();
        @$dom->loadHTML( $html );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $class = $element->getAttribute( 'class' );

            // This is the one we want!
            if ( 'periodic_report_2' == $class ):
                $historyLinks[] = RemitSpiderUSBank::BASE_URL . $element->getAttribute( 'href' );
            endif;
        endforeach;
        return $historyLinks;
    }


    /**
     * @return void
     * @throws \Exception
     */
//    protected function _cacheHistoryLinks(): void {
//        $writeSuccess = file_put_contents( $this->pathToDealLinkSuffixes,
//                                           implode( "\n", $this->dealLinkSuffixes ) );
//        if ( FALSE === $writeSuccess ):
//            throw new \Exception( "Unable to write US Bank Deal link suffixes to cache file: " . $this->pathToDealLinkSuffixes );
//        endif;
//    }
}