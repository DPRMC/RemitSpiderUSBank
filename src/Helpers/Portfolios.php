<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use DPRMC\RemitSpiderUSBank\RemitSpiderUSBankBAK;
use HeadlessChromium\Page;

/**
 *
 */
class Portfolios {

    protected Page   $Page;
    protected Debug  $Debug;
    protected array  $portfolioIds;
    protected string $pathToPortfolioIds;


    /**
     *
     */
    const URL_BASE_PORTFOLIOS = RemitSpiderUSBankBAK::BASE_URL . '/TIR/portfolios?layout=layout&OWASP_CSRFTOKEN=';


    /**
     * @param \HeadlessChromium\Page $Page
     * @param string                 $pathToPortfolioIds
     */
    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToPortfolioIds = '' ) {
        $this->Page               = $Page;
        $this->Debug              = $Debug;
        $this->pathToPortfolioIds = $pathToPortfolioIds;
    }


    public function getAllPortfolioIds( string $csrf ): array {
        $this->Debug->_debug("Getting all Portfolio IDs.");
        // Example:
        // https://trustinvestorreporting.usbank.com/TIR/portfolios?layout=layout&OWASP_CSRFTOKEN=1111-2222-3333-4444-5555-6666-7777-8888
        $this->Page->navigate( self::URL_BASE_PORTFOLIOS . $csrf )
                   ->waitForNavigation( Page::NETWORK_IDLE,
                                        USBankBrowser::NETWORK_IDLE_MS_TO_WAIT );

        $portfolioHTML = $this->Page->getHtml();
        Errors::is404(self::URL_BASE_PORTFOLIOS . $csrf, $portfolioHTML);

        $this->Debug->_debug("Received the HTML containing the Portfolio IDs.");
        $this->portfolioIds = $this->_parseOutUSBankPortfolioIds( $portfolioHTML );
        $this->Debug->_debug("I found " . count($this->portfolioIds) . " Portfolio IDs.");
        $this->_cachePortfolioIds();
        $this->Debug->_debug("Writing the Portfolio IDs to cache.");
        return $this->portfolioIds;
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
        $writeSuccess = file_put_contents( $this->pathToPortfolioIds,
                                           implode( "\n", $this->portfolioIds ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Portfolio IDs to cache file: " . $this->pathToPortfolioIds );
        endif;
    }
}