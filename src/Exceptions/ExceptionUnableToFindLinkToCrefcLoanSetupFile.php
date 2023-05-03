<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;


/**
 *
 */
class ExceptionUnableToFindLinkToCrefcLoanSetupFile extends \Exception {

    /**
     * @var array
     * When looking for the Loan Setup file, these are the TD values I am searching through.
     * Check the text in these TDs to see if they are spelling it any differently.
     * Currently looking for:
     * 'Loan Setup'
     */
    public array $trimmedTds = [];

    public function __construct( string      $message = "",
                                 int         $code = 0,
                                 ?\Throwable $previous = NULL,
                                 array       $trimmedTds = [] ) {
        parent::__construct( $message, $code, $previous );
        $this->trimmedTds = $trimmedTds;
    }
}