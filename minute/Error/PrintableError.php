<?php
/**
 * User: Sanchit <dev@minutephp.com>
 * Date: 7/6/2016
 * Time: 6:18 PM
 */
namespace Minute\Error {

    class PrintableError extends BasicError {
        //children of this error class are printed as-is to the output and not converted to human friendly text error messages
        // e.g. UserLoginError
    }
}