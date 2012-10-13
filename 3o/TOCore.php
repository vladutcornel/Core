<?php

// Multiple sites can use the same instalation
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', $_SERVER['DOCUMENT_ROOT']);
}

// This file should not be moved from the root of Trio Framework files
define('TRIO_DIR', __DIR__);
require_once TRIO_DIR . '/whereis.php';

/**
 * The center of 3OScript redirect mechanism
 * It determines the php file that should be loaded.
 * For non-php files, it just dumpes the contents and sets the Mime type
 * accordinglly
 * @author Cornel Borina <cornel@scoalaweb.com>
 * @package 3oCore
 */
class TOCore {

    /**
     * @staticvar array The extra params of the array
     */
    public static $params = array();
    public static $request;

    /**
     * This loads the requested page from a .php file
     * @param $page string the requested URI
     * @return The loaded filename, without extension
     */
    public static function load($page = '') {
        // if there is no page, we use the index
        if ($page == '')
            $page = 'index';

        // get the virtual directories
        $parts = explode('/', $page);

        if (is_dir(SITE_ROOT . '/' . $page) && file_exists(SITE_ROOT . '/' . $page . '/index.php')) {
            // a directory with index.php was requested
            $page.= '/index.php';
            $parts[] = 'index';
            include $page;
        } elseif (file_exists(SITE_ROOT . '/' . $page) && is_file(SITE_ROOT . '/' . $page)) {
            // load the requested file
            $fileinfo = pathinfo(SITE_ROOT . '/' . $page);

            $last = count($parts) - 1;
            $parts[$last] = $fileinfo['filename'];



            if ($fileinfo['extension'] != 'php') {
                // it's not a php script
                header("Content-Type: " . mime_content_type(SITE_ROOT . '/' . $page));
                echo file_get_contents(SITE_ROOT . '/' . $page);

                die();
            }

            include SITE_ROOT . '/' . $page;
        } elseif (file_exists(SITE_ROOT . '/' . $page . '.php')) {
            // the file was requested without .php extension
            include SITE_ROOT . '/' . $page . '.php';
        } else {
            // no luck so far, we try loading the parent directory
            $parent_dir = implode('/', array_slice($parts, 0, -1));
            if ($parent_dir != '') {
                array_unshift(self::$params, array_slice($parts, -1));
                return self::load($parent_dir);
            }

            if (file_exists(SITE_ROOT . '/index.php')) {
                // try to load the homepage.
                return self::load("index");
            }

            // we still couldn't find a file to load
            echo '<p>Error:file not found</p>';
        }

        // return the filename, so we can figure the class to load
        return $parts[count($parts) - 1];
    }

    /**
     * Tells weather or not this is a AJAX request.
     * The request is tought to be AJAX if HTTP_X_REQUESTED_WITH header is set to 'XMLHttpRequest'
     * or, more reliably, the request parameter(eather via post, or via get) is 'ajax'
     * @return boolean
     */
    public static function isAjax() {
        return
                (TGlobal::server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest')
                ||
                (TGlobal::get('request') == 'ajax');
    }

    /**
     * Tells weather or not this is a CSS request.
     * The request is tought to be CSS if the 'request' parameter(eather via post, or via get) is 'css'
     * @return boolean
     */
    public static function isCss() {
        return (TGlobal::get('request') == 'css');
    }

    /**
     * Tells weather or not this is a JavaScript request.
     * The request is tought to be JavaScript if the 'request' parameter(eather via post, or via get) is 'js'
     * @return boolean
     */
    public static function isJavascript() {
        return (TGlobal::get('request') == 'js');
    }

    /**
     * The main function for the TOCore class. This is loaded by default.
     * It loads the appropriate file and and an object of the main class
     * (that should have the same name as the file)
     *
     * Then it tries to invoke the appropriate method for the request
     * (ajax, javascript, css) or the main() method that should be in all the
     * class files ment for display
     */
    public static function main() {

        // turn on output buffering so nothing is isplayed unless everything is OK
        ob_start();

        //
        $queryArray = array();

        $final = "";
        if (isset($_SERVER['REDIRECT_QUERY_STRING'])) {
            // We are here probably by a redirect, so load the page
            parse_str($_SERVER['REDIRECT_QUERY_STRING'], $queryArray);
            $page = $queryArray['page'];
            $final = self::load($page);
        } else {
            // We are here organic (via include or require),
            // so we should figure the name of the file
            $final = basename($_SERVER['PHP_SELF'], ".php");
        }

        // The class name should only contain letters, numbers or underscores ("_")
        $final_class = preg_replace('/[^a-z0-9_]+/i', '_', $final);
        // the class name should start with a letter, so we add a "P" (Page) at the begining of number names
        // e.g. 404 becomes P404
        if (!preg_match("/^[a-z]/i", $final_class)) {
            $final_class = "P{$final_class}";
        }

        // We have the name, let's run the script...

        if (class_exists($final_class)) {
            $page = new $final_class();
            if (self::isAjax() && method_exists($page, 'ajax')) {
                //run the main AJAX method
                $page->ajax(self::$params);
            } elseif (self::isJavascript() && method_exists($page, 'js')) {
                // run Javascript method
                $page->javascript();
            } elseif (self::isCss() && method_exists($page, 'css')) {
                // run CSS method
                $page->css(self::$params);
            } elseif (method_exists($page, 'main')) {
                // run the main method
                $page->main(self::$params);
            } else {
                // No main method. The fun is over
                die('There is no method main() in ' . $final . '.php <br> The script can not be loaded');
            }
        } else {
            // The class is not declared (corectly)
            die("<p>Can't find the main class.<br>Please create a {$final} class in {$final}.php</p>");
        }

        ob_end_flush();
    }

}

TOCore::main();