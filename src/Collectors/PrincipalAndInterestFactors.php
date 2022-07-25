<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionUnableToFindPrincipalAndInterestTab;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class PrincipalAndInterestFactors {
    protected Page   $Page;
    protected Debug  $Debug;
    protected string $timezone;
    protected string $pathToFactorFiles;

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
                                 string $pathToFactorFiles,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page  = $Page;
        $this->Debug = $Debug;

        $this->pathToFactorFiles = $pathToFactorFiles;
        $this->timezone          = $timezone;
    }


    /**
     * @param string $dealLinkSuffix
     * @param string $pathToDownloadedFiles
     *
     * @return void
     */
    public function getAllByDeal( string $dealLinkSuffix, string $pathToDownloadedFiles ): void {
        try {
            $this->Debug->_debug( "Getting all Principal and Interest Factors for a Deal Link Suffix: " . $dealLinkSuffix );

            $this->Debug->_debug( "About to call _getDealLinks" );

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
            $elements = $this->Page->dom()->search( "//a[contains(text(),'P & I')]" );

            if ( !isset( $elements[ 0 ] ) ):
                throw new ExceptionUnableToFindPrincipalAndInterestTab( "Unable to find a link with the text 'P & I' in it.", 0, null, $dealId, $dealLinkSuffix );
            endif;
            $element = $elements[ 0 ];
            $element->click();
            sleep( 1 );
            $this->Debug->_debug( "Should be on the P&I tab now." );



            // TODO get it to expand the divs
            //$expandButtons = $this->Page->dom()->querySelectorAll( '.min-max-button');
            //$this->Debug->_debug( "I found " . count( $elements ) . " min max buttons." );
            //foreach($expandButtons as $expandButton):
            //    $expandButton->click();
            //endforeach;




            //$html = $this->Page->getHtml();
            //$dom  = new \DOMDocument();
            //@$dom->loadHTML( $html );

            $this->Debug->_screenshot( 'factors_' . urlencode( $dealLinkSuffix ) );
            $this->Debug->_html( 'factors_' . urlencode( $dealLinkSuffix ) );

//            $elements = $dom->getElementsByTagName( 'a' );

            $elements = $this->Page->dom()->querySelectorAll( '.download_factor' );
            //$elements = $this->Page->dom()->querySelectorAll( '.view-factor-summary-link' );

            $this->Debug->_debug( "I found " . count( $elements ) . " links." );

            $factorLinks = [];
            foreach ( $elements as $element ):
                try {
                    $href = $element->getAttribute( 'href' );
                    $dateFromHref = $this->_getDateFromHref( $href );
                    $dateString   = $dateFromHref->format( 'Y-m-d' );
                    $this->Debug->_debug( "clicking on (" . $dateString . "): " . $href  );
                    $dateFromHref = $this->_getDateFromHref( $href );
                    $dateString   = $dateFromHref->format( 'Y-m-d' );
                    $factorLinks[ $dateString ] = $href;
                    $filenameParts = explode('?', basename($href));
                    $filePathToTestFor = $filePath . DIRECTORY_SEPARATOR . $filenameParts[0];

                    if( file_exists($filePathToTestFor)):
                        continue;
                    endif;

                    $element->click(); // New
                    sleep( 1 );
                } catch ( \Exception $exception ) {
                    $this->Debug->_debug( "EXCEPTION: " . $exception->getMessage() );
                }

            endforeach;
            $this->Debug->_debug( "I found " . count( $factorLinks ) . " P & I Factor sheets." );
            $this->stopTime = Carbon::now( $this->timezone );

            return $factorLinks;
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
            throw $exception;
        }
    }


    protected function _getDateFromHref( $href ): Carbon {
        $pattern = '/(\d{2}-\d{2}-\d{4}).csv/';
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


    protected function _getFilePath( string $dealId ): string {
        return $this->pathToFactorFiles . DIRECTORY_SEPARATOR . $dealId;
    }

}
