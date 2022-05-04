<?php

namespace DPRMC\RemitSpiderUSBank\Objects;


class Portfolio extends BaseObject {

    public function __construct( array $data, string $timezone, string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );
    }
}