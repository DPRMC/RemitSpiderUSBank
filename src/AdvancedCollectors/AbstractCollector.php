<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionUnableToTabByText;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
abstract class AbstractCollector {
    protected Page   $Page;
    protected Debug  $Debug;
    protected string $timezone;

    protected ?Carbon $startTime;
    protected ?Carbon $stopTime;

    const LINK    = 'link';       // The history link.
    const DEAL_ID = 'dealId';

    /**
     *
     */
    const BASE_DEAL_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/detail/';

    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page  = $Page;
        $this->Debug = $Debug;
        $this->timezone          = $timezone;
    }



    public function downloadFilesByDealSuffix( string $dealLinkSuffix,
                                               string $tabText,
                                               string $querySelectorForLinks,
                                               string $pathToDownloadedFiles ): array {
        $this->startTime = Carbon::now( $this->timezone );
        $dealId          = $this->_getDealIdFromDealLinkSuffix( $dealLinkSuffix );
        $filePath        = $pathToDownloadedFiles . $dealId;
        $this->Page->setDownloadPath( $filePath );
        $this->Debug->_debug( "Download path set to: " . $filePath );

        try {
            // Example URL:
            // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1234/abc-defg-2001-1
            $this->Page->navigate( self::BASE_DEAL_URL . $dealLinkSuffix )
                       ->waitForNavigation( Page::NETWORK_IDLE, 5000 );

            $this->Debug->_screenshot( 'deal_page_' . urlencode( $dealLinkSuffix ) );
            $this->Debug->_html( 'deal_page_' . urlencode( $dealLinkSuffix ) );


            // Click the P&I Tab
            $elements = $this->Page->dom()->search( "//a[contains(text(),'" . $tabText . "')]" );

            if ( !isset( $elements[ 0 ] ) ):
                throw new ExceptionUnableToTabByText( "Unable to find a link with the text '" . $tabText . "' in it.",
                                                      0,
                                                      null,
                                                      $tabText,
                                                      $dealId,
                                                      $dealLinkSuffix );
            endif;
            $element = $elements[ 0 ];
            $element->click();
            sleep( 1 );
            $this->Debug->_debug( "Should be on the '" . $tabText . "' tab now." );

            $this->Debug->_screenshot( 'factors_' . urlencode( $dealLinkSuffix ) );
            $this->Debug->_html( 'factors_' . urlencode( $dealLinkSuffix ) );

            $elements = $this->Page->dom()->querySelectorAll( $querySelectorForLinks );
            $this->Debug->_debug( "I found " . count( $elements ) . " links." );

            $links = $this->_clickElements($elements);

            $this->Debug->_debug( "I found " . count( $links ) . " " . $tabText . " sheets." );
            $this->stopTime = Carbon::now( $this->timezone );

            return $links;
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
            throw $exception;
        }
    }

    /**
     * @param array $elements
     * @param string $pathToSaveFiles
     *
     * @return array An array of the href's that were clicked.
     */
    abstract protected function _clickElements(array $elements, string $pathToSaveFiles): array;





    protected function _getDealIdFromDealLinkSuffix( string $dealLinkSuffix ): string {
        $parts = explode( '/', $dealLinkSuffix );
        if ( 2 != count( $parts ) ):
            throw new \Exception( "There was a problem getting the deal id from: " . $dealLinkSuffix );
        endif;
        return $parts[ 0 ];
    }

    protected function _getDealNameFromDealLinkSuffix( string $dealLinkSuffix ): string {
        $parts = explode( '/', $dealLinkSuffix );
        if ( 2 != count( $parts ) ):
            throw new \Exception( "There was a problem getting the deal id from: " . $dealLinkSuffix );
        endif;
        return $parts[ 1 ];
    }

}
