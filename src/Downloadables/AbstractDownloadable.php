<?php

namespace DPRMC\RemitSpiderUSBank\Downloadables;


abstract class AbstractDownloadable {

    public string $dealLinkSuffix;
    public string $textOfLabel     = '';
    public string $downloadableUrl = '';

    public string $dealId   = '';
    public string $dealName = '';


    public function __construct( string $dealLinkSuffix, string $textOfLabel, string $downloadableUrl ) {
        $this->dealLinkSuffix  = $dealLinkSuffix;
        $this->textOfLabel     = $textOfLabel;
        $this->downloadableUrl = $downloadableUrl;

        $parts = explode( '/', $dealLinkSuffix );
        if ( isset( $parts[ 0 ] ) ):
            $this->dealId = $parts[ 0 ];
        endif;

        if ( isset( $parts[ 1 ] ) ):
            $this->dealName = $parts[ 1 ];
        endif;
    }
}