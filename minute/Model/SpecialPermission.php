<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 10/8/2016
 * Time: 5:47 AM
 */
namespace Minute\Model {

    class SpecialPermission extends Permission {
        const SAME_USER_OR_IGNORE = 'same_user_or_ignore';  //only the user that created the record can access (read or update) it, but does not throw error if no user is logged in (only applies for read)
    }
}