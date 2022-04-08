<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use DPRMC\RemitSpiderUSBank\RemitSpiderUSBankBAK;
use HeadlessChromium\Page;

/**
 *
 */
class HistoryLinks {


    protected Page   $page;
    protected string $dealLinkPrefix;

    const BASE_DEAL_URL = RemitSpiderUSBankBAK::BASE_URL . '/TIR/public/deals/detail/';

    public function __construct( Page &$page, ) {
        $this->page = $page;
    }


    /**
     * @param \HeadlessChromium\Page $page
     * @param string                 $dealLinkPrefix
     *
     * @return array
     * @throws \HeadlessChromium\Exception\CommunicationException
     */
    public function get( string $dealLinkPrefix ): array {

        $this->dealLinkPrefix = $dealLinkPrefix;

        // Example URL:
        // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1110/abc-defg-2001-1
        $this->page->navigate( self::BASE_DEAL_URL . $dealLinkPrefix )
                   ->waitForNavigation( Page::NETWORK_IDLE, 5000 );
    }
}