# PHP-FileBrowse
A very simple single-file PHP script for browsing a directory of files.

## Use
Configure the constants under 'Settings':

    define("DISKPATH",	"/path/to/files/on/disk/");
    define("WEBPATH",	"/path/to/files/in/web");
    define("DATEFORMAT",    "d-m-y H:i:s");
    define("GALLERYTITLE",  "File Viewer");
    define("BINARYSIZE",	true);
    define("DUMMY_IMAGE", "http://dummyimage.com/400x125");
    $BLACKLIST		= array("..", "."); // silly PHP doesn't support vector constants.
    date_default_timezone_set('UTC');



