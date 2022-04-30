<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;

use Carbon\Carbon;
use HeadlessChromium\Page;

/**
 * There is a lot of common code among every piece of data retrieved from US Bank.
 */
abstract class BaseData {
    protected Page  $Page;
    protected Debug $Debug;

    protected Carbon $startTime;
    protected Carbon $stopTime;
    protected string $lastRunStatus;

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
    protected array $data;

    const META            = 'meta';
    const DATA            = 'data';
    const START_TIME      = 'startTime';
    const STOP_TIME       = 'stopTime';
    const LAST_RUN_STATUS = 'lastRunStatus';

    /**
     * @return void
     */
    public function loadFromCache() {
        $stringCache = file_get_contents( $this->pathToCache );
        $arrayCache  = json_decode( $stringCache, TRUE );

        $this->data          = $arrayCache[ self::DATA ];
        $this->startTime     = unserialize( $arrayCache[ self::META ][ self::START_TIME ] );
        $this->stopTime      = unserialize( $arrayCache[ self::META ][ self::STOP_TIME ] );
        $this->lastRunStatus = $arrayCache[ self::META ][ self::LAST_RUN_STATUS ];
    }


    /**
     * If you don't want to overwrite your cache file...
     * Like if you only did a partial run to ADD TO your cache file...
     *
     * @param array $data
     *
     * @return void
     * @throws \Exception
     */
    protected function _cacheData( array $data ) {

        $dataToWrite = [
            self::META => [
                self::START_TIME      => serialize( $this->startTime ),
                self::STOP_TIME       => serialize( $this->stopTime ),
                self::LAST_RUN_STATUS => 'ok',
            ],
            self::DATA => [
                $data,
            ],
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

        $stringCache = file_get_contents( $this->pathToCache );
        $arrayCache  = json_decode( $stringCache, TRUE );

        $arrayCache[ self::META ] = [
            self::START_TIME      => serialize( $this->startTime ),
            self::STOP_TIME       => serialize( $this->stopTime ),
            self::LAST_RUN_STATUS => $exception->getMessage(),
        ];

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

    abstract protected function _setDataToCache( array $data);

}