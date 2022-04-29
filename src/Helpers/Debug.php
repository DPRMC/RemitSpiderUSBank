<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Clip;
use HeadlessChromium\Page;

/**
 *
 */
class Debug {

    protected Page   $page;
    protected string $pathToScreenshots;
    protected bool   $debug;
    protected string $timezone;

    public function __construct( Page   &$page,
                                 string $pathToScreenshots = '',
                                 bool   $debug = FALSE,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->page              = $page;
        $this->pathToScreenshots = $pathToScreenshots;
        $this->debug             = $debug;
        $this->timezone          = $timezone;
    }


    /**
     * This is just a little helper function to clean up some debug code.
     *
     * @param string                      $suffix
     * @param \HeadlessChromium\Clip|NULL $clip
     *
     * @return void
     * @throws \HeadlessChromium\Exception\CommunicationException
     * @throws \HeadlessChromium\Exception\FilesystemException
     * @throws \HeadlessChromium\Exception\ScreenshotFailed
     */
    public function _screenshot( string $suffix, Clip $clip = NULL ) {

        $now   = Carbon::now( $this->timezone );
        $time  = $now->timestamp;
        $micro = $now->microsecond;

        if ( $this->debug ):
            if ( $clip ):
                $this->page->screenshot( [ 'clip' => $clip ] )
                           ->saveToFile( $time . '_' . $micro . '_' . $suffix . '.jpg' );
            else:
                $this->page->screenshot()
                           ->saveToFile( $time . '_' . $micro . '_' . $suffix . '.jpg' );
            endif;
        endif;
    }


    public function _html( string $filename ) {
        $now   = Carbon::now( $this->timezone );
        $time  = $now->timestamp;
        $micro = $now->microsecond;
        if ( $this->debug ):
            $html = $this->page->getHtml();
            file_put_contents( $this->pathToScreenshots . $time . '_' . $micro . '_' . $filename . '.html', $html );
        endif;
    }

    public function _debug( string $message, bool $die = FALSE ) {
        if ( $this->debug ):
            echo "\n" . $message;
            flush();
            if ( $die ):
                die();
            endif;
        endif;
    }
}