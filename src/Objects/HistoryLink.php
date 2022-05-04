<?php

namespace DPRMC\RemitSpiderUSBank\Objects;

use DPRMC\RemitSpiderUSBank\Collectors\HistoryLinks;

class HistoryLink extends BaseObject {

    public function __construct( array $data, string $timezone, string $pathToCache ) {
        parent::__construct( $data, $timezone,  $pathToCache );
    }


    /**
     * @return string
     * @throws \Exception
     */
    public function getLink(): string {
        if(isset($this->_data[HistoryLinks::LINK])):
            return $this->_data[HistoryLinks::LINK];
        endif;
        throw new \Exception("Key " . HistoryLinks::LINK . " not found in _data for " . self::class);
    }
}
