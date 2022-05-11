<?php

namespace DPRMC\RemitSpiderUSBank\Helpers;


use DPRMC\RemitSpiderUSBank\RemitSpiderUSBank;

class Cache {


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return array
     */
    public static function getDealIdsFromFiles(RemitSpiderUSBank $spider): array {
        $dealIds = [];
        $spider->FileIndex->loadFromCache();
        $files = $spider->FileIndex->getObjects();
        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\File $file
         */
        foreach($files as $file):
            $dealIds[] = $file->getDealId();
        endforeach;
        return $dealIds;
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return array
     */
    public static function getDealIdsFromHistoryLinks(RemitSpiderUSBank $spider): array {
        $dealIds = [];
        $spider->HistoryLinks->loadFromCache();
        $historyLinks = $spider->HistoryLinks->getObjects();
        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\HistoryLink $historyLink
         */
        foreach($historyLinks as $historyLink):
            $dealIds[] = $historyLink->getDealId();
        endforeach;
        return $dealIds;
    }


    /**
     * @param \DPRMC\RemitSpiderUSBank\RemitSpiderUSBank $spider
     *
     * @return array
     */
    public static function getDealIdsFromDeals(RemitSpiderUSBank $spider): array {
        $dealIds = [];
        $spider->Deals->loadFromCache();
        $deals = $spider->Deals->getObjects();
        /**
         * @var \DPRMC\RemitSpiderUSBank\Objects\Deal $deal
         */
        foreach($deals as $deal):
            $dealIds[] = $deal->getDealId();
        endforeach;
        return $dealIds;
    }

}