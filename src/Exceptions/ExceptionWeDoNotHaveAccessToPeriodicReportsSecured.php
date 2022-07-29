<?php

namespace DPRMC\RemitSpiderUSBank\Exceptions;

use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

/**
 *
 */
class ExceptionWeDoNotHaveAccessToPeriodicReportsSecured extends \Exception {

    public string $textToSearchFor;
    public int    $dealId;
    public string $html;

    public function __construct( string      $message = "",
                                 int         $code = 0,
                                 ?\Throwable $previous = NULL,
                                 ?int        $dealId = NULL,
                                 ?string     $textToSearchFor = NULL,
                                 ?string     $html = NULL ) {
        parent::__construct( $message, $code, $previous );

        $this->dealId          = $dealId;
        $this->textToSearchFor = $textToSearchFor;
        $this->html            = $html;
    }
}