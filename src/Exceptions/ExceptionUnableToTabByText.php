<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

/**
 *
 */
class ExceptionUnableToTabByText extends \Exception {

    public string $textToSearchFor;
    public int    $dealId;
    public string $dealLinkSuffix;

    public function __construct( string      $message = "",
                                 int         $code = 0,
                                 ?\Throwable $previous = NULL,
                                 ?string     $textToSearchFor = NULL,
                                 ?int        $dealId = NULL,
                                 ?string     $dealLinkSuffix = NULL ) {
        parent::__construct( $message, $code, $previous );

        $this->textToSearchFor = $textToSearchFor;
        $this->dealId          = $dealId;
        $this->dealLinkSuffix  = $dealLinkSuffix;
    }
}