<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use DPRMC\RemitSpiderUSBank\Collectors\Deals;
use DPRMC\RemitSpiderUSBank\Collectors\HistoryLinks;

/**
 *
 */
class Deal extends BaseObject {

    protected string $portfolioId;
    protected string $dealId;
    protected string $dealName;

    /**
     * @param array  $data
     * @param string $timezone
     * @param string $pathToCache
     */
    public function __construct( array $data, string $timezone, string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );

        $this->portfolioId = $data[ Deals::PORTFOLIO_ID ];
        $this->dealId      = $data[ Deals::DEAL_ID ];
        $this->dealName    = $data[ Deals::DEAL_NAME ];
    }


    /**
     * This link leads to the main Deal page, where we find all the History links.
     * Ex of Deal Link Suffix: 4978/ramp-2006-nc2
     *
     * @return string
     */
    public function getDealLink(): string {
        $dealLinkSuffix = $this->_data[ Deals::DEAL_LINK_SUFFIX ];
        return HistoryLinks::BASE_DEAL_URL . $dealLinkSuffix;
    }


}