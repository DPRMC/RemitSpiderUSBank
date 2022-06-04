<?php

namespace DPRMC\RemitSpiderUSBank\Collectors;

use Carbon\Carbon;
use DPRMC\RemitSpiderUSBank\Helpers\Debug;
use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;
use HeadlessChromium\Page;

/**
 * There is a lot of common code among every piece of data retrieved from US Bank.
 */
abstract class BaseData {
    protected Page  $Page;
    protected Debug $Debug;


    /**
     * @var \Carbon\Carbon|null When the run started.
     */
    protected ?Carbon $startTime;


    /**
     * @var \Carbon\Carbon|null When the run ended;
     */
    protected ?Carbon $stopTime;


    /**
     * @var string|null Ex: "ok" means success. Anything else is an error.
     */
    protected ?string $lastRunStatus;


    /**
     * @var string I use Carbon a lot. Let the user specify their own timezone, if that matters.
     */
    protected string $timezone;


    /**
     * @var string Data gets stored in flat files. Each data type has its own cache location.
     *             In reality there is no reason all data types can't share the same cache location.
     */
    protected string $pathToCache;


    /**
     * @var array A generic array to hold all the data from US Bank.
     */
    public array $data;


    public Carbon $childrenLastPulledAt;

    const META                 = 'meta';
    const DATA                 = 'data';
    const START_TIME           = 'startTime';
    const STOP_TIME            = 'stopTime';
    const LAST_RUN_STATUS      = 'lastRunStatus';
    const ADDED_AT             = 'addedAt';            // When was this data added to the cache.
    const CHILDREN_LAST_PULLED = 'childrenLastPulled'; // When was the info pointed to be this data last loaded.


    public function __construct( Page   &$Page,
                                 Debug  &$Debug,
                                 string $pathToCache,
                                 string $timezone = RemitSpiderUSBank::DEFAULT_TIMEZONE ) {
        $this->Page        = $Page;
        $this->Debug       = $Debug;
        $this->pathToCache = $pathToCache;
        $this->timezone    = $timezone;
        $this->data        = [];
    }

    abstract protected function _setDataToCache( array $data );

    abstract public function getObjects(): array;

    abstract public function notifyParentPullWasSuccessful( RemitSpiderUSBank $spider, $parentId ): void;


    /**
     * Call this method anywhere to load the contents of the cache file into this->data
     * ...along with some other standard meta data.
     * This method should be extended in the child classes to move the contents of
     * this->data into more appropriate locations.
     *
     * @return void
     */
    public function loadFromCache() {
        if ( FALSE == file_exists( $this->pathToCache ) ):
            $this->data          = [];
            $this->lastRunStatus = NULL;
            return;
        endif;

        $stringCache         = file_get_contents( $this->pathToCache );
        $arrayCache          = json_decode( $stringCache, TRUE );
        $this->data          = $arrayCache[ self::DATA ] ?? [];
        $this->startTime     = isset( $arrayCache[ self::META ][ self::START_TIME ] ) ? unserialize( $arrayCache[ self::META ][ self::START_TIME ] ) : NULL;
        $this->stopTime      = isset( $arrayCache[ self::META ][ self::STOP_TIME ] ) ? unserialize( $arrayCache[ self::META ][ self::STOP_TIME ] ) : NULL;
        $this->lastRunStatus = isset( $arrayCache[ self::META ][ self::LAST_RUN_STATUS ] ) ? $arrayCache[ self::META ][ self::LAST_RUN_STATUS ] : 'not set';
    }


    /**
     * @return bool
     */
    public function deleteCache() {
        if ( FALSE == file_exists( $this->pathToCache ) ):
            return TRUE;
        endif;
        return unlink( $this->pathToCache );
    }


    /**
     * If you don't want to overwrite your cache file...
     * Like if you only did a partial run to ADD TO your cache file...
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheData(): void {

        $dataToWrite = [
            self::META => [
                self::START_TIME      => serialize( $this->startTime ),
                self::STOP_TIME       => serialize( $this->stopTime ),
                self::LAST_RUN_STATUS => 'ok',
            ],
            self::DATA => $this->data,
        ];

        $writeSuccess = file_put_contents( $this->pathToCache, json_encode( $dataToWrite ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank Portfolio IDs to cache file: " . $this->pathToCache );
        endif;
    }


    /**
     * When you catch an exception during the run of this class, record the failure in cache.
     *
     * @param \Exception $exception
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheFailure( \Exception $exception ): void {

        if ( FALSE == file_exists( $this->pathToCache ) ):
            file_put_contents( $this->pathToCache, json_encode( [] ) );
        endif;

        $stringCache = file_get_contents( $this->pathToCache );
        $arrayCache  = json_decode( $stringCache, TRUE );

        $arrayCache[ self::META ] = [
            self::START_TIME      => serialize( $this->startTime ),
            self::STOP_TIME       => serialize( $this->stopTime ),
            self::LAST_RUN_STATUS => $exception->getMessage(),
        ];

        $arrayCache[ self::DATA ] = $this->data;

        $writeSuccess = file_put_contents( $this->pathToCache, json_encode( $arrayCache ) );
        if ( FALSE === $writeSuccess ):
            throw new \Exception( "Unable to write US Bank data to cache file: " . $this->pathToCache );
        endif;
    }


    /**
     * Helper function. This returns an MD5 hash used as a unique identifier (array index) for a data "record".
     *
     * @param string $string
     *
     * @return string
     */
    protected function _getMyUniqueId( string $string ): string {
        return md5( $string );
    }


    /**
     * Simple getter function.
     *
     * @return string
     */
    public function getPathToCache(): string {
        return $this->pathToCache;
    }
}