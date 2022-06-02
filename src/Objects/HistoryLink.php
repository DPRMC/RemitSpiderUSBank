<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use DPRMC\RemitSpiderUSBank\Collectors\HistoryLinks;

class HistoryLink extends BaseObject {



    /**
     * @var string|mixed|null
     */
    protected ?string $dealId;

    public function __construct( array $data, string $timezone, string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );
        $this->dealId = $data[ HistoryLinks::DEAL_ID ] ?? NULL;
    }


    /**
     * @return string
     * @throws \Exception
     */
    public function getLink(): string {
        if ( isset( $this->_data[ HistoryLinks::LINK ] ) ):
            return $this->_data[ HistoryLinks::LINK ];
        endif;

        throw new \Exception( "Key HistoryLinks::LINK not found in _data for " . self::class );
    }




    /**
     * A simple getter.
     *
     * @return string
     */
    public function getDealId(): string {
        return $this->dealId;
    }
}
