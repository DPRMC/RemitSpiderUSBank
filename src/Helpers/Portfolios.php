<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;


/**
 *
 */
class Portfolios extends BaseData {

    /**
     * @var array Exists in this->data, but when loading from Cache I load it here for clarity.
     */
    protected array  $portfolioIds;


    /**
     *
     */
    const URL_BASE_PORTFOLIOS = RemitSpiderUSBank::BASE_URL . '/TIR/portfolios?layout=layout&OWASP_CSRFTOKEN=';


    /**
     * @param \HeadlessChromium\Page                 $Page
     * @param \DPRMC\RemitSpiderUSBank\Helpers\Debug $Debug
     * @param string                                 $pathToPortfolioIds
     * @param string                                 $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToPortfolioIds = '',
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page               = $Page;
        $this->Debug              = $Debug;
        $this->pathToCache = $pathToPortfolioIds;
        $this->timezone           = $timezone;
    }


    /**
     * @param string $csrf
     *
     * @return array
     * @throws \DPRMC\RemitSpiderUSBank\Exceptions\Exception404Returned
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\CommunicationException\CannotReadResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\InvalidResponse
     * @throws \HeadlessChromium\Exception\CommunicationException\ResponseHasError
     * @throws \HeadlessChromium\Exception\NavigationExpired
     * @throws \HeadlessChromium\Exception\NoResponseAvailable
     * @throws \HeadlessChromium\Exception\OperationTimedOut
     */
    public function getAllPortfolioIds( string $csrf ): array {
        try {
            $this->Debug->_debug( "Getting all Portfolio IDs." );
            $this->startTime = Carbon::now( $this->timezone );
            // Example:
            // https://trustinvestorreporting.usbank.com/TIR/portfolios?layout=layout&OWASP_CSRFTOKEN=1111-2222-3333-4444-5555-6666-7777-8888
            $this->Page->navigate( self::URL_BASE_PORTFOLIOS . $csrf )
                       ->waitForNavigation( Page::NETWORK_IDLE,
                                            USBankBrowser::NETWORK_IDLE_MS_TO_WAIT );

            $portfolioHTML = $this->Page->getHtml();
            Errors::is404( self::URL_BASE_PORTFOLIOS . $csrf, $portfolioHTML );

            $this->Debug->_debug( "Received the HTML containing the Portfolio IDs." );
            $this->portfolioIds = $this->_parseOutUSBankPortfolioIds( $portfolioHTML );
            $this->Debug->_debug( "I found " . count( $this->portfolioIds ) . " Portfolio IDs." );
            $this->stopTime = Carbon::now( $this->timezone );
            $this->_cacheData($this->portfolioIds);
            $this->Debug->_debug( "Writing the Portfolio IDs to cache." );
            return $this->portfolioIds;
        } catch ( \Exception $exception ) {
            $this->_cacheFailure( $exception );
            throw $exception;
        }
    }


    /**
     * @param string $postLoginHTML
     *
     * @return array
     */
    protected function _parseOutUSBankPortfolioIds( string $postLoginHTML ): array {
        $portfolioIds = [];
        $dom          = new \DOMDocument();
        @$dom->loadHTML( $postLoginHTML );
        $elements = $dom->getElementsByTagName( 'a' );
        foreach ( $elements as $element ):
            $class = $element->getAttribute( 'class' );

            // This is the one we want!
            if ( 'lnk_portfoliotab' == $class ):
                $portfolioIds[] = $element->getAttribute( 'id' );
            endif;
        endforeach;
        return $portfolioIds;
    }


    /**
     * The parent method does the heavy lifting, I just denormalize the data for clarity.
     * @return void
     */
    public function loadFromCache() {
        parent::loadFromCache();
        $this->portfolioIds = $this->data;
    }
}