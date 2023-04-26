<?php

namespace DPRMC\RemitSpiderUSBank\AdvancedCollectors;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\Login;
use DPRMC\RemitSpiderUSBank\Downloadables\CrefcLoanSetupFileDownloadable;
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

    public function __construct( Login  &$Login,
                                 Page   &$Page,
                                 Debug  &$Debug,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Login    = $Login;
        $this->Page     = $Page;
        $this->Debug    = $Debug;
        $this->timezone = $timezone;
    }


    public function getDownloadable( string $dealLinkSuffix ): CrefcLoanSetupFileDownloadable {
        $dealId = $this->getDealIdFromDealLinkSuffix( $dealLinkSuffix );
        $this->Debug->_screenshot( 'start_page_' . $dealId );
        $dealPageLink = self::BASE_DETAIL_URL . $dealLinkSuffix;
        $this->Debug->_debug( "Navigating to deal page link: " . $dealPageLink );
        $this->Page->navigate( $dealPageLink )->waitForNavigation();
        $this->Debug->_screenshot( 'the_deal_page_' . $dealId );
        $this->Debug->_html( 'the_deal_page_' . $dealId );


        $querySelector = "//a[contains(., 'Periodic Reports - Secured')]";
        $selector      = new XPathSelector( $querySelector );
        $position      = $this->Page->mouse()->findElement( $selector )->getPosition();

        $this->Debug->_screenshot( 'the_position_of_periodic_reports_secured', new Clip( 0, 0, $position[ 'x' ], $position[ 'y' ] ) );
        $this->Page->mouse()->move( $position[ 'x' ], $position[ 'y' ] )->click();
        sleep( 2 );
        $this->Debug->_screenshot( 'periodic_reports_secured_' . $dealId );
        $this->Debug->_html( 'periodic_reports_secured_' . $dealId );

        $htmlOfSecuredReports = $this->Page->getHtml();

        $dom = new \DOMDocument();
        @$dom->loadHTML( $htmlOfSecuredReports );

        $tds        = $dom->getElementsByTagName( 'td' );
        $trimmedTds = [];
        foreach ( $tds as $i => $td ):
            $tdText = $td->nodeValue;
            $trimmedTds[ $i ] = trim( $tdText );
        endforeach;


        $indexOfLabel = NULL;
        foreach ( $trimmedTds as $i => $trimmedTd ):
            if ( str_contains( $trimmedTd, 'Loan Setup' ) ):
                $indexOfLabel = $i;
                break;
            endif;
        endforeach;

        if ( is_null( $indexOfLabel ) ):
            // report the td values to Bugsnag,so I can adjust this code.
            // Probably need to change the str_contains above.
            throw new \Exception( "TODO I need to record the lables to Bugsnag or whereever to add them to the str_contains above" );
        endif;

        $textOfLabel         = trim( $tds[ $indexOfLabel ]->nodeValue );
        $dateOfLoanSetupFile = trim( $tds[ $indexOfLabel + 1 ]->nodeValue );

        /**
         * @var \DOMElement $tdWithLink
         */
        $tdWithLink = $tds[ $indexOfLabel + 2 ];
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
                                                               'America/New_York' );
                endif;
            endif;
        endforeach;

        throw new \Exception( "Unable to find yada yada yada." );
    }

    protected function getDealIdFromDealLinkSuffix( string $dealLinkSuffix ): string {
        $dealLinkSuffixParts = explode( '/', $dealLinkSuffix );
        return $dealLinkSuffixParts[ 0 ];
    }


}
