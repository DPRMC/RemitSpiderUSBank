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
    protected string $dealLinkSuffix;

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
        $this->dealLinkSuffix = $this->_data[ Deals::DEAL_LINK_SUFFIX ];
    }


    /**
     * This link leads to the main Deal page, where we find all the History links.
     * Ex of Deal Link Suffix: 4978/ramp-2006-nc2
     *
     * @return string
     */
    public function getDealLink(): string {
        return HistoryLinks::BASE_DEAL_URL . $this->dealLinkSuffix;
    }

    public function getDealLinkSuffix(): string {
        return $this->dealLinkSuffix;
    }


    /**
     * Simple getter.
     * @return string
     */
    public function getPortfolioId(): string {
        return $this->portfolioId;
    }

    /**
     * Simple getter.
     * @return string
     */
    public function getDealName(): string {
        return $this->dealName;
    }


    /**
     * Original: absc_-2006-he2
     * Clean: absc-2006-he2
     * @return string
     */
    public function getCleanDealName(): string {
        $dealName = trim($this->dealName);
        $dealName = strtolower($this->dealName);
        $dealName = str_replace(' ','-',$dealName);
        $dealName = str_replace('_','-',$dealName);
        $dealName = str_replace('--','-',$dealName);
        $dealName = str_replace('---','-',$dealName);
        return $dealName;
    }


    /**
     * Simple getter.
     * @return string
     */
    public function getDealId(): string {
        return $this->dealId;
    }






}