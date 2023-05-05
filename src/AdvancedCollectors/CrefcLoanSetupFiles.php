<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\Login;
use DPRMC\RemitSpiderUSBank\Downloadables\CrefcLoanSetupFileDownloadable;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionDoNotHaveAccessToThisDeal;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionNotLoggedIn;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionOurAccessToThisPeriodicReportSecuredIsPending;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionTimedOutWaitingForClickToLoad;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionUnableToFindLinkToCrefcLoanSetupFile;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionUnableToTabByText;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Clip;
use HeadlessChromium\Cookies\Cookie;
use HeadlessChromium\Dom\Selector\XPathSelector;
use HeadlessChromium\Page;

/**
 *
 */
class CrefcLoanSetupFiles {

    protected Login  $Login;
    protected Page   $Page;
    protected Debug  $Debug;
    protected string $timezone;

    protected ?Carbon $startTime;
    protected ?Carbon $stopTime;

    const BASE_DETAIL_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/detail/';

    const MAX_CYCLES_TO_WAIT_AFTER_CLICK_TO_LOAD = 10;

    public function __construct( Login  &$Login,
                                 Page   &$Page,
                                 Debug  &$Debug,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Login    = $Login;
        $this->Page     = $Page;
        $this->Debug    = $Debug;
        $this->timezone = $timezone;
    }


    /**
     * @param string $dealLinkSuffix
     * @return CrefcLoanSetupFileDownloadable
     * @throws ExceptionDoNotHaveAccessToThisDeal
     * @throws ExceptionNotLoggedIn
     * @throws ExceptionOurAccessToThisPeriodicReportSecuredIsPending
     * @throws ExceptionTimedOutWaitingForClickToLoad
     * @throws ExceptionUnableToFindLinkToCrefcLoanSetupFile
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
    public function getDownloadable( string $dealLinkSuffix ): CrefcLoanSetupFileDownloadable {
        $dealId = $this->getDealIdFromDealLinkSuffix( $dealLinkSuffix );
        $this->Debug->_screenshot( 'start_page_' . $dealId );
        $dealPageLink = self::BASE_DETAIL_URL . $dealLinkSuffix;
        $this->Debug->_debug( "Navigating to deal page link: " . $dealPageLink );
        $this->Page->navigate( $dealPageLink )->waitForNavigation();
        $this->Debug->_screenshot( 'the_deal_page_' . $dealId );
        $this->Debug->_html( 'the_deal_page_' . $dealId );

//        $querySelector = "//a[contains(., 'Periodic Reports - Secured')]";
//        $selector      = new XPathSelector( $querySelector );
//        $position      = $this->Page->mouse()->findElement( $selector )->getPosition();
//        $this->Debug->_screenshot( 'the_position_of_periodic_reports_secured', new Clip( 0, 0, $position[ 'x' ], $position[ 'y' ] ) );
//        $this->Page->mouse()->move( $position[ 'x' ], $position[ 'y' ] )->click();
//        sleep( 2 );
//        $this->Debug->_screenshot( 'periodic_reports_secured_' . $dealId );
//        $this->Debug->_html( 'periodic_reports_secured_' . $dealId );
//        $htmlOfSecuredReports = $this->Page->getHtml();

        $htmlOfPeriodicReportsSecuredReports = $this->_getPeriodicReportsSecuredHtml( $dealId );
        $htmlOfPeriodicReports               = $this->_getPeriodicReportsHtml( $dealId );

        $combinedHTML = $htmlOfPeriodicReportsSecuredReports . $htmlOfPeriodicReports;

        if ( str_contains( $combinedHTML, 'Guest' ) ):
            throw new ExceptionNotLoggedIn( "You are not logged in.",
                                            0,
                                            NULL );
        endif;

        if ( str_contains( $combinedHTML, 'You do not have access to this deal' ) ):
            throw new ExceptionDoNotHaveAccessToThisDeal( "You don't have access to this deal.",
                                                          0,
                                                          NULL,
                                                          $dealLinkSuffix );
        endif;


        if ( str_contains( $combinedHTML, 'request to access this deal or feature is pending' ) ):
            throw new ExceptionOurAccessToThisPeriodicReportSecuredIsPending( "Access to this deal is pending",
                                                                              0,
                                                                              NULL,
                                                                              $dealLinkSuffix );
        endif;

        $dom = new \DOMDocument();
        //@$dom->loadHTML( $htmlOfPeriodicReportsSecuredReports );
        @$dom->loadHTML( $combinedHTML );

        $tds        = $dom->getElementsByTagName( 'td' );
        $trimmedTds = [];
        foreach ( $tds as $i => $td ):
            $tdText           = $td->nodeValue;
            $trimmedTds[ $i ] = trim( $tdText );
        endforeach;


        $indexOfLabel = NULL;
        foreach ( $trimmedTds as $i => $trimmedTd ):
            if ( str_contains( $trimmedTd, 'Loan Setup' ) ):
                $indexOfLabel = $i;
                break;
            elseif ( str_contains( $trimmedTd, 'Electronic Data File' ) ):
                $indexOfLabel = $i;
                break;
            endif;
        endforeach;

        if ( is_null( $indexOfLabel ) ):
            // report the td values to Bugsnag,so I can adjust this code.
            // Probably need to change the str_contains above.
            throw new ExceptionUnableToFindLinkToCrefcLoanSetupFile( "Unable to find the index of the label to the CREFC Loan Setup file.",
                                                                     0,
                                                                     NULL,
                                                                     $trimmedTds );
        endif;

        $textOfLabel         = trim( $tds[ $indexOfLabel ]->nodeValue );
        $dateOfLoanSetupFile = trim( $tds[ $indexOfLabel + 1 ]->nodeValue );

        /**
         * @var \DOMElement $tdWithLink
         */
        $tdWithLink         = $tds[ $indexOfLabel + 2 ];
        $tdWithLinkChildren = $tdWithLink->childNodes;

        /**
         * @var \DOMElement|\DOMText $child
         */
        foreach ( $tdWithLinkChildren as $child ):

            if ( is_a( $child, \DOMElement::class ) ):
                $href = trim( $child->getAttribute( 'href' ) );
                if ( ! empty( $href ) ):
                    $urlToCrefcLoanSetupFile = RemitSpiderUSBank::BASE_URL . $href;
                    return new CrefcLoanSetupFileDownloadable( $dealLinkSuffix,
                                                               $textOfLabel,
                                                               $urlToCrefcLoanSetupFile,
                                                               $dateOfLoanSetupFile,
                                                               $this->timezone );
                endif;
            endif;
        endforeach;

        throw new \Exception( "Unable to find yada yada yada." );
    }

    protected function getDealIdFromDealLinkSuffix( string $dealLinkSuffix ): string {
        $dealLinkSuffixParts = explode( '/', $dealLinkSuffix );
        return $dealLinkSuffixParts[ 0 ];
    }


    protected function _getPeriodicReportsSecuredHtml( string $dealId ): string {
        $cycles = 0;

        $querySelector = "//a[contains(., 'Periodic Reports - Secured')]";
        $selector      = new XPathSelector( $querySelector );
        $position      = $this->Page->mouse()->findElement( $selector )->getPosition();
        $this->Debug->_screenshot( 'the_position_of_periodic_reports_secured', new Clip( 0, 0, $position[ 'x' ], $position[ 'y' ] ) );
        $this->Page->mouse()->move( $position[ 'x' ], $position[ 'y' ] )->click();
        sleep( 2 );

        do {
            $htmlOfSecuredReports = $this->Page->getHtml();
            if ( ! str_contains( $htmlOfSecuredReports, 'Loading Contents' ) ):
                $this->Debug->_screenshot( 'periodic_reports_secured_LOADED_' . $dealId );
                $this->Debug->_html( 'periodic_reports_secured_LOADED_' . $dealId );
                return $htmlOfSecuredReports;
            endif;
            sleep( 1 );
        } while ( $cycles <= self::MAX_CYCLES_TO_WAIT_AFTER_CLICK_TO_LOAD );

        $this->Debug->_screenshot( 'periodic_reports_secured_TIMEDOUT_' . $dealId );
        $this->Debug->_html( 'periodic_reports_secured_TIMEDOUT_' . $dealId );
        throw new ExceptionTimedOutWaitingForClickToLoad( "I waited over " . self::MAX_CYCLES_TO_WAIT_AFTER_CLICK_TO_LOAD . " seconds for the Secured Periodic Reports to Load.", 0, NULL, $dealId );
    }

    protected function _getPeriodicReportsHtml( string $dealId ): string {
        $cycles = 0;

        $querySelector = "//a[contains(., 'Periodic Reports')]";
        $selector      = new XPathSelector( $querySelector );
        $position      = $this->Page->mouse()->findElement( $selector )->getPosition();
        $this->Debug->_screenshot( 'the_position_of_periodic_reports_unsecured', new Clip( 0, 0, $position[ 'x' ], $position[ 'y' ] ) );
        $this->Page->mouse()->move( $position[ 'x' ], $position[ 'y' ] )->click();
        sleep( 2 );

        do {
            $htmlOfSecuredReports = $this->Page->getHtml();
            if ( ! str_contains( $htmlOfSecuredReports, 'Loading Contents' ) ):
                $this->Debug->_screenshot( 'periodic_reports_unsecured_LOADED_' . $dealId );
                $this->Debug->_html( 'periodic_reports_unsecured_LOADED_' . $dealId );
                return $htmlOfSecuredReports;
            endif;
            sleep( 1 );
        } while ( $cycles <= self::MAX_CYCLES_TO_WAIT_AFTER_CLICK_TO_LOAD );

        $this->Debug->_screenshot( 'periodic_reports_unsecured_TIMEDOUT_' . $dealId );
        $this->Debug->_html( 'periodic_reports_unsecured_TIMEDOUT_' . $dealId );
        throw new ExceptionTimedOutWaitingForClickToLoad( "I waited over " . self::MAX_CYCLES_TO_WAIT_AFTER_CLICK_TO_LOAD . " seconds for the Unsecured Periodic Reports to Load.", 0, NULL, $dealId );
    }


}
