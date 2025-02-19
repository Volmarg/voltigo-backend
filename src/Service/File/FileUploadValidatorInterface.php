<?php

namespace App\Service\File;

/**
 * Either describes the file validator or contains code to prevent the validator for getting bloated
 */
interface FileUploadValidatorInterface
{
    /**
     * @link https://stackoverflow.com/questions/3592834/bad-file-extensions-that-should-be-avoided-on-a-file-upload-site
     */
    public const GLOBALLY_DENIED_FILE_EXTENSIONS = [
        "ini",
        "db",
        "sql",
        "html",
        "html5",
        "desktop",
        "js",
        "jsx",
        "bat",
        "exe",
        "cmd",
        "sh",
        "php",
        "php3",
        "php4",
        "php5",
        "php7",
        "php8",
        "pl",
        "cgi",
        "dll",
        "com",
        "torrent",
        "js",
        "app",
        "jar",
        "pif",
        "vb",
        "vbscript",
        "wsf",
        "asp",
        "cer",
        "csr",
        "jsp",
        "drv",
        "sys",
        "ade",
        "adp",
        "bas",
        "chm",
        "cpl",
        "crt",
        "csh",
        "fxp",
        "hlp",
        "hta",
        "inf",
        "ins",
        "isp",
        "jse",
        "htaccess",
        "htpasswd",
        "ksh",
        "lnk",
        "mdb",
        "mde",
        "mdt",
        "mdw",
        "msc",
        "msi",
        "msp",
        "mst",
        "ops",
        "pcd",
        "prg",
        "reg",
        "scr",
        "sct",
        "shb",
        "shs",
        "url",
        "vbe",
        "vbs",
        "wsc",
        "wsf",
        "wsh",
    ];

    public const GLOBALLY_DENIED_MIME_TYPES = [
        "application/x-httpd-php",
        "application/octet-stream",
        "application/x-7z-compressed",
        "application/zip",
        "application/xhtml+xml",
        "application/x-tar",
        "application/x-sh",
        "application/json",
        "text/javascript",
        "application/java-archive",
        "text/html",
        "application/gzip",
        "application/x-bzip2",
        "application/x-bzip",
    ];

    public const DISALLOWED_CHARACTERS_REGEXP = "[^\w\s\d\-_~,;\[\]\(\).]|([\.]{2,})";
    public const MULTI_EXTENSION_REGEXP       = ".*\.(.*\.)+";
}