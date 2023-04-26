<?php

namespace DPRMC\RemitSpiderUSBank\Downloadables;


use Carbon\Carbon;

class CrefcLoanSetupFileDownloadable extends AbstractDownloadable {

    public ?Carbon $dateOfLoanSetupFile = NULL;


    public function __construct( string  $dealLinkSuffix,
                                 string  $textOfLabel,
                                 string  $downloadableUrl,
                                 ?string $stringDateOfLoanSetupFile = NULL,
                                 string  $timezone = 'America/New_York' ) {
        parent::__construct( $dealLinkSuffix, $textOfLabel, $downloadableUrl );

        if ( $stringDateOfLoanSetupFile ):
            try {
                $this->dateOfLoanSetupFile = Carbon::parse( $stringDateOfLoanSetupFile, $timezone );
            } catch ( \Exception $exception ) {
                $this->dateOfLoanSetupFile = NULL;
            }
        endif;
    }
}