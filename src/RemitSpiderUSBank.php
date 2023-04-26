<?php

namespace DPRMC\RemitSpiderUSBank;

use DPRMC\RemitSpiderUSBank\AdvancedCollectors\PeriodicReportsSecured;
use DPRMC\RemitSpiderUSBank\AdvancedCollectors\PrincipalAndInterestFactors;
use DPRMC\RemitSpiderUSBank\Collectors\Deals;
use DPRMC\RemitSpiderUSBank\Collectors\FileIndex;
use DPRMC\RemitSpiderUSBank\Collectors\Portfolios;
use DPRMC\RemitSpiderUSBank\Collectors\USBankBrowser;
use DPRMC\RemitSpiderUSBank\Collectors\HistoryLinks;
use DPRMC\RemitSpiderUSBank\Collectors\Login;
use HeadlessChromium\Cookies\CookiesCollection;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use HeadlessChromium\Page;


/**
 *
 */
class RemitSpiderUSBank {


    public USBankBrowser               $USBankBrowser;
    public Debug                       $Debug;
    public Login                       $Login;
    public Portfolios                  $Portfolios;
    public Deals                       $Deals;
    public HistoryLinks                $HistoryLinks;
    public FileIndex                   $FileIndex;
    public PrincipalAndInterestFactors $PrincipalAndInterestFactors;
    public PeriodicReportsSecured      $PeriodicReportsSecured;


    protected bool   $debug;
    protected string $pathToScreenshots;

    protected string $pathToPortfolioIds;
    protected string $pathToDealLinkSuffixes;
    protected string $pathToHistoryLinks;
    protected string $pathToFileIndex;
    protected string $timezone;

    protected array $portfolioIds;
    protected array $dealIds;

    protected Page $page;


    const  BASE_URL                                = 'https://trustinvestorreporting.usbank.com';
    const  PORTFOLIO_IDS_FILENAME                  = '_portfolio_ids.json';
    const  DEAL_LINK_SUFFIXES_FILENAME             = '_deal_link_suffixes.json';
    const  HISTORY_LINKS_FILENAME                  = '_history_links.json';
    const  FILE_INDEX_FILENAME                     = '_file_index.json';

    const DEFAULT_TIMEZONE = 'America/New_York';

    /**
     * TESTING, not sure if this will work.
     *
     * @var CookiesCollection Saving the cookies post login. When the connection dies for no reason, I can restart the
     *      session.
     */
    public CookiesCollection $cookies;


    // https://trustinvestorreporting.usbank.com/TIR/public/deals/detail/1710/abn-amro-2003-4
    protected array $linksToDealsBySecurityId = [];


    public function __construct( string $chromePath,
                                 string $user,
                                 string $pass,
                                 bool   $debug = FALSE,
                                 string $pathToScreenshots = '',
                                 string $pathToPortfolioIds = '',
                                 string $pathToDealLinkSuffixes = '',
                                 string $pathToHistoryLinks = '',
                                 string $pathToFileIndex = '',
                                 string $pathToFileDownloads = '',
                                 string $timezone = self::DEFAULT_TIMEZONE
    ) {

        $this->debug                             = $debug;
        $this->pathToScreenshots                 = $pathToScreenshots;
        $this->pathToPortfolioIds                = $pathToPortfolioIds . self::PORTFOLIO_IDS_FILENAME;
        $this->pathToDealLinkSuffixes            = $pathToDealLinkSuffixes . self::DEAL_LINK_SUFFIXES_FILENAME;
        $this->pathToHistoryLinks                = $pathToHistoryLinks . self::HISTORY_LINKS_FILENAME;
        $this->pathToFileIndex                   = $pathToFileIndex . self::FILE_INDEX_FILENAME;

        $this->timezone = $timezone;

        $this->USBankBrowser = new USBankBrowser( $chromePath );
        $this->USBankBrowser->page->setDownloadPath( $pathToFileDownloads );

        $this->Debug = new Debug( $this->USBankBrowser->page,
                                  $pathToScreenshots,
                                  $debug,
                                  $this->timezone );

        $this->Login = new Login( $this->USBankBrowser->page,
                                  $this->Debug,
                                  $user,
                                  $pass,
                                  $this->timezone );

        $this->Portfolios = new Portfolios( $this->USBankBrowser->page,
                                            $this->Debug,
                                            $this->pathToPortfolioIds,
                                            $this->timezone );

        $this->Deals = new Deals( $this->USBankBrowser->page,
                                  $this->Debug,
                                  $this->pathToDealLinkSuffixes,
                                  $this->timezone );

        $this->HistoryLinks = new HistoryLinks( $this->USBankBrowser->page,
                                                $this->Debug,
                                                $this->pathToHistoryLinks,
                                                $this->timezone );

        $this->FileIndex = new FileIndex( $this->USBankBrowser->page,
                                          $this->Debug,
                                          $this->pathToFileIndex,
                                          $this->timezone );

        $this->PrincipalAndInterestFactors = new PrincipalAndInterestFactors( $this->USBankBrowser->page,
                                                                              $this->Debug,
                                                                              $this->timezone );
        $this->PeriodicReportsSecured      = new PeriodicReportsSecured( $this->USBankBrowser->page,
                                                                         $this->Debug,
                                                                         $this->timezone );
    }


    /**
     *
     */
    private function _loadIds() {
        if ( file_exists( $this->pathToPortfolioIds ) ):
            $this->portfolioIds = file( $this->pathToPortfolioIds );
        else:
            file_put_contents( $this->pathToPortfolioIds, NULL );
        endif;

        if ( file_exists( $this->pathToDealLinkSuffixes ) ):
            $this->dealIds = file( $this->pathToDealLinkSuffixes );
        else:
            file_put_contents( $this->pathToDealLinkSuffixes, NULL );
        endif;
    }


    /**
     * A little helper function to turn on debugging from the top level object.
     * @return void
     */
    public function enableDebug(): void {
        $this->debug = TRUE;
        $this->Debug->enableDebug();
        $this->Debug->_debug( "Debug has been enabled." );
    }


    /**
     * @return void
     */
    public function disableDebug(): void {
        $this->debug = FALSE;
        $this->Debug->disableDebug();
        $this->Debug->_debug( "Debug has been disabled." );
    }
}