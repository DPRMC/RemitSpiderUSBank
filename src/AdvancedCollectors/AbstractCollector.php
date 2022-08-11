<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;


use Carbon\Carbon;
use DPRMC\FIMS\API\V1\Console\Commands\Custodians\USBank\USBankDownloadDeals;
use DPRMC\FIMS\API\V1\Modules\Custodians\USBank\Models\USBankDeal;
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

    protected string $tabText               = '';
    protected string $querySelectorForLinks = '';

    const LINK    = 'link';       // The history link.
    const DEAL_ID = 'dealId';

    /**
     *
     */
    const BASE_DEAL_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/detail/';

    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page     = $Page;
        $this->Debug    = $Debug;
        $this->timezone = $timezone;
    }


    /**
     * I added the $misc parameter in the case I need additional resources passed into a particular
     * implementation of this abstract method.
     *
     * @param array                                  $elements
     * @param string                                 $pathToSaveFiles
     * @param \HeadlessChromium\Page                 $page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $debug
     * @param int                                    $dealId
     * @param array                                  $misc I am cheating here. See notes above.
     *
     * @return array
     */
    abstract protected function _clickElements( array  $elements,
                                                string $pathToSaveFiles,
                                                Page   $page,
                                                Debug  $debug,
                                                int    $dealId,
                                                array  $misc = [] ): array;


    /**
     * @param string $dealLinkSuffix
     * @param string $pathToDownloadedFiles Should have a trailing slash.
     *
     * @return array
     * @throws \DPRMC\RemitSpiderUSBank\Exceptions\ExceptionUnableToTabByText
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
    public function downloadFilesByDealSuffix( string $dealLinkSuffix,
                                               string $pathToDownloadedFiles ): array {

        if ( empty( $this->tabText ) ):
            throw new \Exception( "Developer: Don't forget you need to set tabText in the child class." );
        endif;

        if ( empty( $this->querySelectorForLinks ) ):
            throw new \Exception( "Developer: Don't forget you need to set querySelectorForLinks in the child class." );
        endif;

        $this->startTime    = Carbon::now( $this->timezone );
        $dealId             = $this->_getDealIdFromDealLinkSuffix( $dealLinkSuffix );
        $filePathWithDealId = $pathToDownloadedFiles . $dealId;

        $this->Page->setDownloadPath( $filePathWithDealId );
        $this->Debug->_debug( "Download path set to: " . $filePathWithDealId );

        try {
            // Example URL:
            // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1234/abc-defg-2001-1
            $this->Page->navigate( self::BASE_DEAL_URL . $dealLinkSuffix )
                       ->waitForNavigation( Page::NETWORK_IDLE, 5000 );

            $this->Debug->_screenshot( 'the_deal_page_' . urlencode( $dealLinkSuffix ) );
            $this->Debug->_html( 'the_deal_page_' . urlencode( $dealLinkSuffix ) );


            // Click the TAB with text in $tabText
            $elements = $this->Page->dom()->search( "//a[contains(text(),'" . $this->tabText . "')]" );

            if ( !isset( $elements[ 0 ] ) ):
                $this->Debug->_debug( "Unable to find a link with the text '" . $this->tabText . "' in it." );
                throw new ExceptionUnableToTabByText( "Unable to find a link with the text '" . $this->tabText . "' in it.",
                                                      0,
                                                      NULL,
                                                      $this->tabText,
                                                      $dealId,
                                                      $dealLinkSuffix );
            endif;
            $element = $elements[ 0 ];
            $element->click();
            sleep( 1 );
            $this->Debug->_debug( "Should be on the '" . $this->tabText . "' tab now." );

            $this->Debug->_screenshot( 'tab_main_' . urlencode( $dealLinkSuffix ) );
            $this->Debug->_html( 'tab_main_' . urlencode( $dealLinkSuffix ) );

            // GET ELEMENTS OF INTEREST ON THAT PAGE.
            $elements = $this->Page->dom()->querySelectorAll( $this->querySelectorForLinks );
            $this->Debug->_debug( "I found " . count( $elements ) . " links." );

            $links = $this->_clickElements( $elements,
                                            $filePathWithDealId,
                                            $this->Page,
                                            $this->Debug,
                                            $dealId );

            $this->Debug->_debug( "I found " . count( $links ) . " " . $this->tabText . " sheets." );
            $this->stopTime = Carbon::now( $this->timezone );

            return $links;
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
            throw $exception;
        }
    }


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
