<?php

namespace DPRMC\RemitSpiderUSBank\Objects;


class Portfolio extends BaseObject {

    public string $portfolioId;

    public function __construct( string $portfolioId, array $data, string $timezone, string $pathToCache ) {
        parent::__construct( $data, $timezone, $pathToCache );
        $this->portfolioId = $portfolioId;
    }
}