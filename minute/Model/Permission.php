<?php
/**
 * User: Sanchit <dev@svift.io>
 * Date: 4/5/2016
 * Time: 10:19 PM
 */
namespace Minute\Model {

    class Permission {
        const SAME_USER = 'same_user';  //only the user that created the record can access (read or update) it, throws error if no user is logged in
        const ANY_USER  = 'any_user';   //any logged in user can access the record
        const GUEST     = 'guest';      //only non-logged in users may access the record
        const EVERYONE  = 'all';        //everyone on the site can access the record
        const NOBODY    = 'none';
    }
}