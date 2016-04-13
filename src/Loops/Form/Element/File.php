<?php
/**
 * This file is part of the Loops framework.
 *
 * @author Lukas <lukas@loopsframework.com>
 * @license https://raw.githubusercontent.com/loopsframework/base/master/LICENSE
 * @link https://github.com/loopsframework/base
 * @link https://loopsframework.com/
 * @version 0.1
 */

namespace Loops\Form\Element;

use Loops;
use Loops\Annotations\Access\ReadOnly;
use Loops\Annotations\Access\Expose;
use Loops\Annotations\Listen;
use Loops\Annotations\Session\SessionVar;
use Loops\Exception;
use Loops\Form\Element\Filter\Text as TextFilter;
use Loops\Misc;
use Loops\Session\SessionTrait;

/**
 * @todo evaluate PUT file upload
 */
class File extends Text {
    use SessionTrait;

    /**
     * @var string $file the absolute filepath (inside the loops upload directory)
     * @ReadOnly("getFile")
     * @SessionVar
     * @Expose
     */
    protected $file;

    /**
     * @var string $filename the filename
     * @ReadOnly("getFilename")
     * @SessionVar
     * @Expose
     */
    protected $filename;

    /**
     * @var integer $filesize the size of the file
     * @ReadOnly("getFilesize")
     * @Expose
     */
    protected $filesize;
    
    /**
     * @ReadOnly
     */
    protected $storage_dir;
    
    /**
     * @ReadOnly
     */
    protected $timeout;

    protected $error_access_post;
    protected $error_no_file;
    protected $error_delete_failed;
    protected $error_upload_failed;
    protected $error_upload_failed_detail;
    protected $error_move_failed;
    protected $error_createdir_failed;
    
    public function __construct($default = NULL, $validators = [], $filters = [], $context = NULL, Loops $loops = NULL) {
        if($default !== NULL) {
            throw new Exception("File can't have a default value.");
        }
        
        parent::__construct($default, $validators, $filters, $context, $loops);
        
        $loops       = $this->getLoops();
        $config      = $loops->getService('config');
        $application = $loops->GetService('application');
        
        $this->storage_dir = @$config->upload->storage_dir ?: "{$application->cache_dir}/upload/";
        $this->timeout     = @$config->upload->timeout ?: 60*60*24*7;
    }
    
    /**
     * Get the upload directory
     */
    public function getUploadDir() {
        return $this->storage_dir."/".md5($this->getLoopsId());
    }
    
    private function jsonResult($error = FALSE, $message = "", $key = FALSE, $params = []) {
        $this->response->setJson();
        $this->response->setStatusCode($error ?: 200);
        
        $result["success"] = !(bool)$error;
        
        if($error) {
            if($key) {
                array_unshift($params, $this->$key ?: gettext($message));
                $result["error"] = call_user_func_array("sprintf", $params);
            }
            else {
                $result["error"] = $message;
            }
        }
        
        return json_encode($result);
    }
    
    /**
     * Deletes a previously uploaded file. Must be accessed with the POST method.
     *
     * An json response will be generated and send back to the client.
     * { 'success': TRUE|FALSE, [ 'error': errorstring ] }
     */
    public function deleteAction($parameter) {
        try {
            if(!$this->request->isPost()) {
                return $this->jsonResult(400, 'Please access via POST method.', 'error_access_post');
            }
    
            if(!$this->getFile()) {
                return $this->jsonResult(400, 'No file was uploaded.', 'error_no_file');
            }
    
            $this->initFromSession();
            
            $this->file = NULL;
    
            $this->filename = NULL;

            $this->filesize = NULL;
    
            $this->saveToSession();
            
            if(!$this->deleteFile()) {
                return $this->jsonResult(400, 'Could not delete file.', 'error_delete_failed');
            }
    
            return $this->jsonResult();
        }
        catch(Exception $e) {
            return $this->jsonResult(500, $e->getMessage());
        }
    }

    /**
     * Sends a previously uploaded file if it exists.
     *
     * The first parameter can be set to the uploaded filename to force correct behaviour from the
     * browser regarding the filename. Otherwise the filename will be defined in the header and
     * becomes a little bit more unstable since cross-browser checks have to be made.
     */
    public function downloadAction($parameter) {
        if(!$this->getFile()) {
            return 404;
        }

        if(count($parameter) > 1) {
            return 404;
        }

        if($parameter && $parameter[0] != $this->filename) {
            return 404;
        }
        
        return Misc::servefile($this->file, $parameter ? FALSE : $this->filename);
    }

    /**
     * Stores a received file. The request must be encoded as formdata.
     *
     * The first file in the formdata will be used, every other file is ignored.
     *
     * An json response will be generated and send back to the client.
     * { 'success': TRUE|FALSE, [ 'error': errorstring ] }
     */
    public function uploadAction($parameter) {
        try {
            if(!$files = $this->request->files()) {
                return $this->jsonResult(400, 'Failed to upload file.', 'error_upload_failed');
            }
    
            foreach($files as $file) {
                if($error = $file->getError()) {
                    return $this->jsonResult(400, "Failed to upload file '%s'.", "error_upload_failed_detail", [$file->getError()]);
                }
            }
            
            $this->initFromSession();
            
            $this->deleteFile();
            
            $dir = $this->getUploadDir();

            if(!Misc::recursiveMkdir($dir, 0700)) {
                return $this->jsonResult(500, "Failed to create upload directory at '%s'.", "error_createdir_failed", [$dir]);
            }
    
            foreach($files as $file) {
                $target = "$dir/".$file->getName();
        
                if(!$file->moveTo($target)) {
                    return $this->jsonResult(500, 'Failed to move file.', 'error_move_failed');
                }
            }
    
            $this->file = $target;
    
            $this->filename = $file->getName();
    
            $this->filesize = $file->getSize();
    
            $this->saveToSession();
            
            $this->cleanFiles();
    
            return $this->jsonResult();
        }
        catch(Exception $e) {
            return $this->jsonResult(500, $e->getMessage());
        }
    }

    private function cleanFiles() {
        $bordertime = time() - $this->timeout;
        
        foreach(scandir($this->storage_dir) as $file) {
            if(in_array($file, [".", ".."])) continue;
            if(fileatime("{$this->storage_dir}/$file") > $bordertime) continue;
            Misc::recursiveUnlink("{$this->storage_dir}/$file");
        }
    }
    
    public function getValue($strict = FALSE) {
        return $this->getFilename();
    }

    public function getFile() {
        $this->initFromSession();
        return $this->file;
    }
    
    public function getFilename() {
        $this->initFromSession();
        return $this->filename;
    }
    
    public function getFilesize() {
        $this->initFromSession();
        return $this->filesize;
    }

    /**
     * @Listen("Form\onCleanup")
     */
    protected function deleteFile() {
        return Misc::recursiveUnlink($this->getUploadDir());
    }
    
    /**
     * @Listen("Form\onCleanup")
     */
    protected function clearSession() {
        SessionTrait::clearSession();
    }

    /**
     * @Listen("Sesssion\onInit")
     */
    protected function autoSetFileSize($values) {
        if($this->file && file_exists($this->file)) {
            $this->filesize = filesize($this->file);
        }
    }
}