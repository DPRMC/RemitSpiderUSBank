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
class PeriodicReportsSecured {
    protected Page   $Page;
    protected Debug  $Debug;
    protected string $timezone;

    protected ?Carbon $startTime;
    protected ?Carbon $stopTime;


    const LINK    = 'link';       // The history link.
    const DEAL_ID = 'dealId';


    const TAB_TEXT = 'Periodic Reports - Secured';


    /**
     *
     */
    const BASE_DEAL_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/detail/';

    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page  = $Page;
        $this->Debug = $Debug;
        $this->timezone    = $timezone;
    }


    /**
     * @param string $dealLinkSuffix
     * @param string $pathToDownloadedFiles
     *
     * @return void
     */
    public function getAllByDeal( string $dealLinkSuffix, string $pathToDownloadedFiles ): void {
        try {
            $this->Debug->_debug( "Getting all Periodic Reports - Secured for a Deal Link Suffix: " . $dealLinkSuffix );

            $this->Debug->_debug( "About to call downloadFilesByDealSuffix" );

            $factorLinks = $this->downloadFilesByDealSuffix( $dealLinkSuffix, $pathToDownloadedFiles );
            $this->Debug->_debug( "Done calling _getDealLinks" );
            foreach ( $factorLinks as $date => $factorLink ):
                $contents = file_get_contents( RemitSpiderUSBank::BASE_URL . $factorLink );
                $filename = $this->_getFilenameFromFactorLink( $factorLink );
                $dealId   = $this->_getDealIdFromDealLinkSuffix( $dealLinkSuffix );
                $filePath = $this->_getFilePath( $dealId );
                $written  = file_put_contents( $filePath . $filename,
                                               $contents );
                if ( !$written ):
                    throw new \Exception( "Unable to write contents of file to: " . $filePath );
                else:
                    $this->Debug->_debug( "File written to: " . $filePath );
                endif;
            endforeach;


        } catch ( \Exception $exception ) {
            $this->Debug->_debug( $exception->getMessage() );
        }

    }


    public function downloadFilesByDealSuffix( string $dealLinkSuffix, string $pathToDownloadedFiles ): array {
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
            $elements = $this->Page->dom()->search( "//a[contains(text(),'" . self::TAB_TEXT . "')]" );

            if ( !isset( $elements[ 0 ] ) ):
                throw new ExceptionUnableToTabByText( "Unable to find a link with the text '" . self::TAB_TEXT . "' in it.",
                                                      0,
                                                      NULL,
                                                      self::TAB_TEXT,
                                                      $dealId,
                                                      $dealLinkSuffix );
            endif;
            $element = $elements[ 0 ];
            $element->click();
            sleep( 1 );
            $this->Debug->_debug( "Should be on the " . self::TAB_TEXT . " tab now." );

            $this->Debug->_screenshot( 'periodic_reports_secured_' . urlencode( $dealLinkSuffix ) );
            $this->Debug->_html( 'periodic_reports_secured_' . urlencode( $dealLinkSuffix ) );

            $elements = $this->Page->dom()->querySelectorAll( '.periodic_report_extension' );

            $this->Debug->_debug( "I found " . count( $elements ) . " links." );

            $factorLinks = [];
            foreach ( $elements as $element ):
                try {
                    /**
                     * @var string $href https://trustinvestorreporting.usbank.com/TIR/public/deals/populateReportDocument/49656429/ZIP
                     */
                    $href         = $element->getAttribute( 'href' );

                    dump($href);
                    //$dateFromHref = $this->_getDateFromHref( $href );
                    //$dateString   = $dateFromHref->format( 'Y-m-d' );
                    //$this->Debug->_debug( "clicking on (" . $dateString . "): " . $href );
                    //$dateFromHref               = $this->_getDateFromHref( $href );
                    //$dateString                 = $dateFromHref->format( 'Y-m-d' );
                    //$factorLinks[ $dateString ] = $href;
                    //$filenameParts              = explode( '?', basename( $href ) );
                    //$filePathToTestFor          = $filePath . DIRECTORY_SEPARATOR . $filenameParts[ 0 ];

                    //if ( file_exists( $filePathToTestFor ) ):
                    //    continue;
                    //endif;
                    //
                    //$element->click(); // New
                    sleep( 1 );
                } catch ( \Exception $exception ) {
                    $this->Debug->_debug( "EXCEPTION: " . $exception->getMessage() );
                }

            endforeach;
            $this->Debug->_debug( "I found " . count( $links ) . " " . self::TAB_TEXT . " sheets." );
            $this->stopTime = Carbon::now( $this->timezone );

            return $factorLinks;
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
            throw $exception;
        }
    }


    protected function _getDateFromHref( $href ): Carbon {
        $pattern = '/(\d{2}-\d{2}-\d{4})/';
        $found   = preg_match( $pattern, $href, $matches );
        if ( 1 !== $found ):
            throw new \Exception( "Could not find the date in this href: " . $href );
        endif;

        return Carbon::createFromFormat( 'm-d-Y', $matches[ 1 ] );
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


    /**
     * Example factorLink:
     * /TIR/public/deals/10101/downloadcomponentfactorsummaries/lxs-2007-6-05-25-2007.csv?OWASP_CSRFTOKEN=N8ZM-SJK6-P7PA-QT26-JA6A-TL2Q-NSCU-DUQA
     *
     * @param string $factorLink
     *
     * @return string
     */
    protected function _getFilenameFromFactorLink( string $factorLink ): string {
        $parts       = explode( '/', $factorLink );
        $endingParts = explode( '?', $parts[ 5 ] );
        return $endingParts[ 0 ];
    }


}
