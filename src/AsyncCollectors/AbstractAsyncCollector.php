<?php

namespace DPRMC\RemitSpiderUSBank\AsyncCollectors;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Collectors\Login;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionDoNotHaveAccessToThisDeal;
use DPRMC\RemitSpiderUSBank\Exceptions\ExceptionOurAccessToThisPeriodicReportSecuredIsPending;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Communication\Message;
use HeadlessChromium\Page;

/**
 *
 */
abstract class AbstractAsyncCollector {

    protected Login  $Login;
    protected Page   $Page;
    protected Debug  $Debug;
    protected string $timezone;

    protected ?Carbon $startTime;
    protected ?Carbon $stopTime;

    const BASE_DETAIL_URL = RemitSpiderUSBank::BASE_URL . '/TIR/public/deals/detail/';

    const MAX_CYCLES_TO_WAIT_AFTER_CLICK_TO_LOAD = 10;


    public string $productType;

    public function __construct( Login  &$Login,
                                 Page   &$Page,
                                 Debug  &$Debug,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Login    = $Login;
        $this->Page     = $Page;
        $this->Debug    = $Debug;
        $this->timezone = $timezone;
    }


    protected function _preAsyncCall( string $dealLinkSuffix ) {
        $dealId = $this->_getDealIdFromDealLinkSuffix( $dealLinkSuffix );
        $this->Debug->_screenshot( 'start_page_' . $dealId );
        $dealPageLink = self::BASE_DETAIL_URL . $dealLinkSuffix;
        $this->Debug->_debug( "Navigating to deal page: " . $dealPageLink );
        $this->Page->navigate( $dealPageLink )->waitForNavigation();
        $this->Debug->_screenshot( 'the_deal_page_' . $dealId );
        $this->Debug->_html( 'the_deal_page_' . $dealId );

        $csrf       = $this->Login->csrf;
        $currentUrl = $this->Page->getCurrentUrl();

        $this->setProductType($this->Page->getHtml());


        $this->Page->getSession()->sendMessageSync( new Message( 'Network.setExtraHTTPHeaders', [
            'headers' => [
                'ADRUM'              => 'isAjax:true',
                'Accept'             => '*/*',
                'Accept-Language'    => 'en-US,en;q=0.9',
                'Connection'         => 'keep-alive',
                'DNT'                => '1',
                'OWASP_CSRFTOKEN'    => $csrf,
                'Referer'            => $currentUrl,
                'Sec-Fetch-Dest'     => 'empty',
                'Sec-Fetch-Mode'     => 'cors',
                'Sec-Fetch-Site'     => 'same-origin',
                'User-Agent'         => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
                'X-Requested-With'   => 'XMLHttpRequest',
                'sec-ch-ua'          => '"Chromium";v="112", "Google Chrome";v="112", "Not:A-Brand";v="99"',
                'sec-ch-ua-mobile'   => '?0',
                'sec-ch-ua-platform' => 'macOS',
            ],
        ] ) );
    }


    /**
     * A little helper function.
     * @param string $dealLinkSuffix
     * @return string
     */
    protected function _getDealIdFromDealLinkSuffix( string $dealLinkSuffix ): string {
        $dealLinkSuffixParts = explode( '/', $dealLinkSuffix );
        return $dealLinkSuffixParts[ 0 ];
    }


    /**
     * @param string $asyncUrl
     * @param string $screenshotTag
     * @return string
     * @throws ExceptionDoNotHaveAccessToThisDeal
     * @throws ExceptionOurAccessToThisPeriodicReportSecuredIsPending
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
    protected function _getHtml(string $asyncUrl, string $screenshotTag): string {
        try {
            $this->Debug->_debug( "Async URL: " . $asyncUrl );
            $this->Page->navigate( $asyncUrl )->waitForNavigation(Page::NETWORK_IDLE);
            $this->Debug->_screenshot( 'async_' . $screenshotTag );
            $this->Debug->_html( 'async_' . $screenshotTag );
            $html = $this->Page->getHtml();




            if ( str_contains( $html, 'You do not have access to this deal' ) ):
                throw new ExceptionDoNotHaveAccessToThisDeal( "You don't have access to this deal.",
                                                              0,
                                                              NULL,
                                                              $asyncUrl );
            endif;


            if ( str_contains( $html, 'request to access this deal or feature is pending' ) ):
                throw new ExceptionOurAccessToThisPeriodicReportSecuredIsPending( "Access to this deal is pending",
                                                                                  0,
                                                                                  NULL,
                                                                                  $asyncUrl );
            endif;

            return $html;
        } catch (\Exception $exception) {
            return '';
        }

    }


    /**
     * @param string $html
     * @return void
     */
    public function setProductType(string $html ): void {
        $dom = new \DOMDocument();
        @$dom->loadHTML( $html );
        /**
         * @var \DOMNodeList $elements
         */
        $elements    = $dom->getElementsByTagName( 'label' );
        $numElements = $elements->count();
        $productType = '';
        for ( $i = 0; $i < $numElements; $i++ ):
            $text = trim( $elements->item( $i )->textContent );
            if ( str_contains( $text, 'Product Type:' ) ):
                $productType = trim( $elements->item( $i + 1 )->textContent );
                break;
            endif;
        endfor;

        $this->productType = $productType;
    }
}
