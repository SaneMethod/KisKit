<?php
/**
 * Copyright (c) Christopher Keefer, 2014. See LICENSE distributed with this software
 * for full license terms and conditions.
 *
 * File Helper - offers common solutions to uploading, downloading a file, determining file mime type, reading
 * a file's contents.
 */
namespace sanemethod\kiskit\system\helpers;

use \StdClass;

class FileHelper{

    private $chunkSize = 1048576; // File chunk size in bytes: 1024*1024 (one KiB)
    private $uploadsEnabled = false;

    function __construct(){
        $this->uploadsEnabled = is_dir(UPLOAD_FILE_PATH) && is_writable(UPLOAD_FILE_PATH);
    }

    /**
     * Offer a file for download if the file exists. Determine file mime.
     * Read the file to the output stream on KB at a time.
     * @see FileHelper::determineMime
     *
     * @param $path
     * @param $filename
     * @return bool
     */
    function offerDownload($path, $filename = null){
        if (!file_exists($path)) return false;

        $mime = $this->determineMime($path);
        $fsize = filesize($path);
        $bytesWritten = 0;

        // Send appropriate download header
        $this->sendDownloadHeaders($mime, $path, $fsize, $filename);

        ob_end_clean();
        $fRes = fopen($path, 'rb');
        $outRes = @fopen('php://output', 'wb');
        while(!feof($fRes))
        {
            $bytesWritten += fwrite($outRes, fread($fRes, $this->chunkSize));
        }
        fclose($fRes);
        fclose($outRes);

        return $bytesWritten == $fsize;
    }

    /**
     * Handle (potentially chunked) uploads.
     *
     * @param null|string $filename
     * @param null|string $upload_dir
     * @param string $returnType Mime type of content returned from server after receiving file (or chunks).
     * @return array
     */
    function handleUpload($filename = null, $upload_dir = null, $returnType = 'application/json'){
        if ($this->uploadsEnabled == false && !isset($upload_dir)){
            return false;
        }

        $file = new StdClass();

        $this->sendUploadHeaders($returnType);

        // Parse the Content-Range header
        // Content-Range: bytes startByte-endByte/totalBytes (#-#/#)
        $content_range = isset($_SERVER['HTTP_CONTENT_RANGE']) ?
            preg_split('/[^0-9]+/', $_SERVER['HTTP_CONTENT_RANGE']) : null;
        $file->size = $content_range ? $content_range[3] : $_SERVER['CONTENT_LENGTH'];
        // Get the non-standard header for the file name, if available, and format appropriately
        $file->name = $this->prepFileName(isset($filename) ? $filename : isset($_SERVER['HTTP_X_FILE_NAME']) ?
            $_SERVER['HTTP_X_FILE_NAME'] : 'tmp');
        // Get the non-standard header for the file type, if available
        $file->type = isset($_SERVER['HTTP_X_FILE_TYPE']) ?
            $_SERVER['HTTP_X_FILE_TYPE'] : $this->determineMime($file->name);
        // Determine upload path
        $file->path = isset($upload_dir) ? $upload_dir : UPLOAD_FILE_PATH;

        if (!is_dir($file->path))
        {
            mkdir($file->path, 0755);
        }
        $file->path .= $file->name; // Expect that file->path will have any needed trailing slashes
        // Determine whether we're appending to an existing file, or writing from scratch
        $append = $content_range && $content_range[1] != 0 && is_file($file->path);

        $inputStream = fopen('php://input', 'rb');
        $write_sucess = file_put_contents(
            $file->path,
            $inputStream,
            ($append) ? FILE_APPEND : 0
        );
        fclose($inputStream);

        $hupReturn = array('file'=>$file, 'success'=>$write_sucess);
        if ($content_range) $hupReturn['content_range'] = $content_range;

        return $hupReturn;
    }

    /**
     * Determine mime type of path string (or a string that will represent a file name). Attempt to use FileInfo
     * first, then fallback to mime_content_type, then to guessing based on extension.
     *
     * @param string $path
     * @return mixed|string
     */
    function determineMime($path){
        $mime = 'application/octet-stream';
        $isFile = file_exists($path);

        if (function_exists('finfo_file') && $isFile)
        {
            // Use finfo to determine mime type
            $fRes = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($fRes, $path);
            finfo_close($fRes);
        }
        else if (function_exists('mime_content_type') && $isFile)
        {
            // Use deprecated mime_content_type to determine mime type
            $mime = mime_content_type($path);
        }
        else if (strpos($path, '.') !== false)
        {
            // Make a guess at the mime type based on the extension
            if (file_exists(SERVER_ROOT . 'config/mimes.php'))
            {
                include(SERVER_ROOT . 'config/mimes.php');
                $ext = explode('.', $path);
                $ext = end($ext);
                if (isset($mimes[$ext])) $mime = $mimes[$ext];
            }
        }

        return $mime;
    }

    /**
     * Send appropriate headers to indicate to the browser that a download is imminent.
     *
     * @param $mime
     * @param $path
     * @param $fsize
     * @param null $filename
     */
    protected function sendDownloadHeaders($mime, $path, $fsize, $filename = null){
        header("Content-Type: {$mime}");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Disposition: attachment; filename="'.
            ((isset($filename)) ? $filename : basename($path)) . '"');
        header("Expires: 0");
        header("Pragma: public");
        header("Content-Length: {$fsize}");
    }

    /**
     * Send appropriate headers to indicate browser shouldn't cache response, and indicate type of return.
     */
    protected function sendUploadHeaders($returnType){
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header("Expires: 0");
        header("Content-Type: {$returnType}");
    }

    /**
     * Strip invalid characters from the file name offered us by the upload.
     *
     * @param $name
     * @return string
     */
    protected function prepFileName($name){
        $name = trim(basename(stripslashes($name)), ".\x00..\x20");
        return $name;
    }

}