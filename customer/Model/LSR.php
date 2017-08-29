<?php
/**
 * Created by PhpStorm.
 * User: Abraham
 * Date: 6/13/2017
 * Time: 3:48 PM
 */

namespace Ls\Customer\Model;

class LSR
{
    // REGISTRY PATHS
    const REGISTRY_LOYALTY_LOGINRESULT = 'lsr-l-lr';
    const REGISTRY_LOYALTY_WATCHNEXTSAVE = 'lsr-l-cwns';
    const REGISTRY_LOYALTY_WATCHNEXTSAVE_ADDED = 'lsr-l-cwns-a';
    const REGISTRY_LOYALTY_WATCHNEXTSAVE_REMOVED = 'lsr-l-cwns-r';

    // SESSION KEYS
    const SESSION_CUSTOMER_SECURITYTOKEN = 'lsr-s-c-st';
    const SESSION_CUSTOMER_CARDID = 'lsr-s-c-cid';
    const SESSION_CUSTOMER_LSRID = 'lsr-s-c-lid';
}
