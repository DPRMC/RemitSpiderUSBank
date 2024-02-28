<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 *
 */
class Exception404Returned extends \Exception {

    public string $url = '';

    public function __construct( string $message = "", int $code = 0, ?\Throwable $previous = NULL, string $url = '' ) {
        parent::__construct( $message, $code, $previous );

        $this->url = $url;
    }
}