<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class Portfolios {

    protected Page  $Page;
    protected Debug $Debug;

    protected string $pathToPortfolioIds;
    protected string $timezone;

    protected Carbon $startTime;
    protected Carbon $stopTime;


    // CACHE
    protected array  $portfolioIds;
    protected string $lastRunStatus;

    const META            = 'meta';
    const DATA            = 'data';
    const START_TIME      = 'startTime';
    const STOP_TIME       = 'stopTime';
    const LAST_RUN_STATUS = 'lastRunStatus';

    /**
     *
     */
    const URL_BASE_PORTFOLIOS = RemitSpiderUSBank::BASE_URL . '/TIR/portfolios?layout=layout&OWASP_CSRFTOKEN=';


    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToPortfolioIds = '',
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page               = $Page;
        $this->Debug              = $Debug;
        $this->pathToPortfolioIds = $pathToPortfolioIds;
        $this->timezone           = $timezone;
    }


    /**
     * @return void
     */
    public function loadFromCache() {
        $stringCache = file_get_contents( $this->pathToPortfolioIds );
        $arrayCache  = json_decode( $stringCache, TRUE );

        $this->portfolioIds  = $arrayCache[ 'data' ];
        $this->startTime     = unserialize( $arrayCache[ 'meta' ][ self::START_TIME ] );
        $this->stopTime      = unserialize( $arrayCache[ 'meta' ][ self::STOP_TIME ] );
        $this->lastRunStatus = $arrayCache[ 'meta' ][ self::LAST_RUN_STATUS ];
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
            $this->_cachePortfolioIds();
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
     * @return void
     * @throws \Exception
     */
    protected function _cachePortfolioIds(): void {
        $dataToWrite = [
            self::META => [
                self::START_TIME      => serialize( $this->startTime ),
                self::STOP_TIME       => serialize( $this->stopTime ),
                self::LAST_RUN_STATUS => 'ok',
            ],
            self::DATA => [
                $this->portfolioIds,
            ],
        ];

        $writeSuccess = file_put_contents( $this->pathToPortfolioIds, json_encode( $dataToWrite ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Portfolio IDs to cache file: " . $this->pathToPortfolioIds );
        endif;
    }


    /**
     * When you catch an exception during the run of this class, record the failure in cache.
     *
     * @param \Exception $exception
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheFailure( \Exception $exception ): void {

        $stringCache = file_get_contents( $this->pathToPortfolioIds );
        $arrayCache  = json_decode( $stringCache, TRUE );

        $arrayCache[ self::META ] = [
            self::START_TIME      => serialize( $this->startTime ),
            self::STOP_TIME       => serialize( $this->stopTime ),
            self::LAST_RUN_STATUS => $exception->getMessage(),
        ];

        $writeSuccess = file_put_contents( $this->pathToPortfolioIds, json_encode( $arrayCache ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Portfolio IDs to cache file: " . $this->pathToPortfolioIds );
        endif;
    }
}