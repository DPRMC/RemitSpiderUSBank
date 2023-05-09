<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

/**
 *
 */
class ExceptionDoNotHaveAccessToThisDeal extends \Exception {

    public string $url = '';

    public function __construct( string $message = "", int $code = 0, ?\Throwable $previous = NULL, string $url = '' ) {
        parent::__construct( $message, $code, $previous );

        $this->url = $url;
    }
}