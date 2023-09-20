<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

/**
 * They will block your IP address if you hit their system too frequently.
 */
class ExceptionIpHasBeenBlocked extends \Exception {

    public string $html;

    public function __construct( string      $message = "",
                                 int         $code = 0,
                                 ?\Throwable $previous = NULL,
                                 ?string     $html = NULL ) {
        parent::__construct( $message, $code, $previous );

        $this->html            = $html;
    }
}