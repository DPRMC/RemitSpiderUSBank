<?php
namespace DPRMC\RemitSpiderUSBank;

class RemitSpiderUSBank {

    const URL_LOGIN_FORM = 'https://gctinvestorreporting.bnymellon.com';

    protected string $user;
    protected string $pass;


    /**
     * @param string $user
     * @param string $pass
     */
    public function __construct(string $user, string $pass){
        $this->user = $user;
        $this->pass = $pass;
    }



}