<?php

namespace DPRMC\RemitSpiderUSBank\Objects;


use DPRMC\RemitSpiderUSBank\Collectors\FileIndex;

class File extends BaseObject {

    protected string $dealId;

    public function __construct( array  $data,
                                 string $timezone,
                                 string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );

        $this->dealId = $data[ FileIndex::DEAL_ID ];
    }


    public function getDealId(): string {
        return $this->dealId;
    }
}
