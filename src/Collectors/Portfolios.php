<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Objects\Portfolio;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;


/**
 * A given US Bank login can have multiple Portfolios under it.
 * The only thing I really need from here is the Portfolio ID
 * ALL other US Bank data can be retrieved given a Portfolio ID.
 * It all starts here!
 */
class Portfolios extends BaseData {

    /**
     * @var array
     */
    public array $portfolioIds;


    /**
     *
     */
    const URL_BASE_PORTFOLIOS = RemitSpiderUSBank::BASE_URL . '/TIR/portfolios?layout=layout&OWASP_CSRFTOKEN=';


    // CACHE
    const PORTFOLIO_ID = 'portfolioId';

    /**
     * @param \HeadlessChromium\Page                    $Page
     * @param \DPRMC\RemitSpiderUSBank\Collectors\Debug $Debug
     * @param string                                    $pathToPortfolioIds
     * @param string                                    $timezone
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToPortfolioIds = '',
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page        = $Page;
        $this->Debug       = $Debug;
        $this->pathToCache = $pathToPortfolioIds;
        $this->timezone    = $timezone;
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
    public function getAll( string $csrf ): array {
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

            $this->_setDataToCache( $this->portfolioIds );
            $this->_cacheData();
            $this->Debug->_debug( "Writing the Portfolio IDs to cache." );
            return $this->getObjects();
        } catch ( \Exception $exception ) {
            $this->stopTime = Carbon::now( $this->timezone );
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
                $portfolioId                  = $element->getAttribute( 'id' );
                $portfolioIds[ $portfolioId ] = $portfolioId;
            endif;
        endforeach;
        return $portfolioIds;
    }


    /**
     * The parent method does the heavy lifting, I just denormalize the data for clarity.
     *
     * @return void
     */
    public function loadFromCache() {
        parent::loadFromCache();
        $this->portfolioIds = array_keys( $this->data );
    }


    /**
     * @param array $data
     *
     * @return void
     */
    protected function _setDataToCache( array $data ) {
        foreach ( $data as $id => $portfolioId ):
            $this->data[ $portfolioId ] = [
                BaseData::ADDED_AT    => Carbon::now( $this->timezone ),
                BaseData::LAST_PULLED => NULL,
                self::PORTFOLIO_ID    => $portfolioId,
            ];
        endforeach;
    }


    /**
     * @return array
     */
    public function getObjects(): array {
        $objects = [];
        foreach ( $this->data as $portfolioId => $data ):
            $objects[] = new Portfolio( $portfolioId, $data, $this->timezone, $this->pathToCache );
        endforeach;
        return $objects;
    }
}