<?php

namespace DPRMC\RemitSpiderUSBank;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use HeadlessChromium\BrowserFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class RemitSpiderUSBank {


    protected string                                        $chromePath;
    protected \HeadlessChromium\Browser\ProcessAwareBrowser $browser;
    protected string                                        $user;
    protected string                                        $pass;
    protected bool                                          $debug;
    protected string                                        $pathToScreenshots;

    protected $page;

    const URL_LOGIN = 'https://usbtrustgateway.usbank.com/portal/login.do';

//    const BASE_URI  = 'https://usbtrustgateway.usbank.com';
//    const URL_HOME  = '/portal';
//    const URL_LOGIN = '/portal/login.do';
//    // Open application URL
//    //'TIR-Ext','https://trustinvestorreporting.usbank.com/TIR/portal/','TrustInvestorReporting'
//    // /portal/public/openApplication.do?appName="+applicationName+"&appUrl="+appUrl, aName, params);
//    const URL_OPEN_APPLICATION = '/portal/public/openApplication.do?appName=TIR-Ext&appUrl=https://trustinvestorreporting.usbank.com/TIR/portal/';
//    const URL_TIR_PORTFOLIOS = 'https://trustinvestorreporting.usbank.com/TIR/portal/';


    protected Client    $guzzleClient;
    protected CookieJar $jar;


    /**
     * @param string $user
     * @param string $pass
     */
    public function __construct( string $chromePath,
                                 string $user,
                                 string $pass,
                                 bool   $debug = FALSE,
                                 string $pathToScreenshots = '' ) {
        $this->chromePath        = $chromePath;
        $this->user              = $user;
        $this->pass              = $pass;
        $this->debug             = $debug;
        $this->pathToScreenshots = $pathToScreenshots;


        $browserFactory = new BrowserFactory( $this->chromePath );
        // starts headless chrome
        $this->browser = $browserFactory->createBrowser( [
                                                             'headless'        => TRUE,         // disable headless mode
                                                             'connectionDelay' => 0.8,           // add 0.8 second of delay between each instruction sent to chrome,
                                                             //'debugLogger'     => 'php://stdout', // will enable verbose mode
                                                             'windowSize'      => [ 500, 440 ],
                                                             'enableImages'    => FALSE,
                                                         ] );

        // creates a new page and navigate to an url
        $this->page = $this->browser->createPage();


//        $this->jar = new \GuzzleHttp\Cookie\CookieJar();
//
//
//        $config             = [
//            // Base URI is used with relative requests
//            'base_uri'   => self::BASE_URI,
//            // You can set any number of default request options.
//            'timeout'    => 60.0,
//            'cookies'    => TRUE,
//            'exceptions' => FALSE,
//        ];
//        $this->guzzleClient = new Client( $config );
    }


    /**
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function login(): bool {
        $this->page->navigate( self::URL_LOGIN )->waitForNavigation();

        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_first_page.jpg' );
        endif;


        $this->page->evaluate( "document.querySelector('#uname').value = '" . $this->user . "';" );
        $this->page->evaluate( "document.querySelector('#pword').value = '" . $this->pass . "';" );

        // DEBUG
        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_filled_in_user_pass.jpg' );
        endif;

//        $this->page->mouse()
//             ->move( 111, 454 )
//             ->click();
//$this->page->waitForReload();
//        sleep(5);


//        $evaluation = $this->page->evaluate( "document.querySelector('input[name='login']').submit();" );
        //$evaluation = $this->page->evaluate( "document.querySelector(input[name='login']).value = 'poop';" );
//        $evaluation = $this->page->evaluate( 'document.querySelector("body > div:nth-child(1) > div.span-19.align-left > div > div:nth-child(4) > form").submit();' );
//        $evaluation->waitForPageReload();


        $js = "document.querySelector('button[type=\"submit\"]').click();";


        $evaluation = $this->page->evaluate( $js );
        $evaluation->waitForPageReload();




        if ( $this->debug ):
            $this->page->screenshot()->saveToFile( time() . '_' . microtime() . '_something_was_clicked.jpg' );
        endif;

        return TRUE;


//        $response = $this->guzzleClient->request( 'GET', self::URL_HOME, [
//            'debug' => $this->debug,
//        ] );
//
//        $response = $this->guzzleClient->request( 'GET', self::URL_LOGIN, [
//            'debug' => $this->debug,
//            'query' => [
//                'userID'   => $this->user . '@usb',
//                'userName' => $this->user,
//                'password' => $this->pass,
//                'method'   => 'Log In',
//            ],
//        ] );
//
//
//        $text     = (string)$response->getBody();
//        $response = NULL; // Free up memory
//
//        if ( $this->isLoggedIn( $text ) ):
//            return TRUE;
//        endif;
//
//        throw new \Exception( "Login failed." );
    }

    public function goTo() {
//        $response = $this->guzzleClient->request( 'GET', self::URL_OPEN_APPLICATION, [
//            'debug' => $this->debug,
//        ] );
//        $text     = (string)$response->getBody();
//        $response = NULL; // Free up memory
//        var_dump( $text );
//
//        sleep( 2 );
//
//
//        try {
//            $response = $this->guzzleClient->request( 'GET', self::URL_TIR_PORTFOLIOS, [
//                'debug' => $this->debug,
//            ] );
//            $text     = (string)$response->getBody();
//            $response = NULL; // Free up memory
//            var_dump( $text );
//        } catch ( \GuzzleHttp\Exception\RequestException $e ) {
//            /**
//             * Here we actually catch the instance of GuzzleHttp\Psr7\Response
//             * (find it in ./vendor/guzzlehttp/psr7/src/Response.php) with all
//             * its own and its 'Message' trait's methods. See more explanations below.
//             *
//             * So you can have: HTTP status code, message, headers and body.
//             * Just check the exception object has the response before.
//             */
//            if ( $e->hasResponse() ) {
//                $response = $e->getResponse();
//                var_dump( $response->getStatusCode() );                  // HTTP status code;
//                var_dump( $response->getReasonPhrase() );                // Response message;
//                var_dump( (string)$response->getBody() );                // Body, normally it is JSON;
//                var_dump( json_decode( (string)$response->getBody() ) ); // Body as the decoded JSON;
//                var_dump( $response->getHeaders() );                     // Headers array;
//                var_dump( $response->hasHeader( 'Content-Type' ) );      // Is the header presented?
//                var_dump( $response->getHeader( 'Content-Type' )[ 0 ] ); // Concrete header value;
//            }
//        }


    }


    /**
     * @param string $text
     *
     * @return bool
     */
    protected function isLoggedIn( string $text ): bool {
        $testString = 'Welcome,';
        if ( TRUE === str_contains( $text, $testString ) ):
            return TRUE;
        endif;
        return FALSE;
    }


}