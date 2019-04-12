<?php
/**
 * Class ETRepoCache that allows basic caching by saving/loading data to/from disk. This is usually done as
 * serialized PHP objects.
 */
class ETRepoCache {
    /**
     * directory for cache files
     * @var string
     */
    static private $CACHE_DIR = 'cache';
    static private $FINGERPRINTS_FILE_NAME = 'fingerprint_all_cache_objects';

    /**
     * Save data to a cache file named $name.
     * @param string $name cache file name
     * @param mixed $data data to save
     * @param bool $serialize serialize data before saving to disk
     * @throws Exception
     */
    public function save($name, $data, $serialize=true) {
        if ($serialize) {
            $dataToWrite = serialize($data);
            $fopenOption = 'wb';
        } else {
            $dataToWrite = $data;
            $fopenOption = 'w';
        }

        $cacheFile = $this->getCacheFileName($name);

        $fHandle = fopen($cacheFile, $fopenOption);
        if (!$fHandle) {
            throw new Exception(sprintf("could not open cache file for writing: '%s'", $cacheFile));
        }

        fwrite($fHandle, $dataToWrite);

        fclose($fHandle);
        
        $this->saveFingerprint($name);
    }


    /**
     * Retrieve cached data from cache store named $name.
     * @param string $name cache store file name
     * @param bool $unserialize unserialize data after loading from disk
     * @return mixed data loaded from cache store
     * @throws Exception
     */
    public function retrieve($name, $unserialize=true) {
        $cacheFile = $this->getCacheFileName($name);

        $fHandle = fopen($cacheFile, $unserialize ? 'rb' : 'r');
        if (!$fHandle) {
            throw new Exception(sprintf("could not open cache file for reading: '%s'", $cacheFile));
        }

        $dataFromFile = fread($fHandle, filesize($cacheFile));

        if ($unserialize) {
            $data = unserialize($dataFromFile);
        } else {
            $data = $dataFromFile;
        }


        if ($data === false) {
            throw new Exception(sprintf("could not unserialize data from cache file: '%s'", $cacheFile));
        }

        return $data;
    }


    /**
     * Check if cache store named $name exists.
     * @param string $name cache file name
     * @return bool
     */
    public function exists($name) {
    	if (file_exists($this->getCacheFileName($name))){
    		return $this->compareFingerprint($name);
    	}
        return FALSE;
    }


    /**
     * Get relative path to cache file named $name.
     * @param string $name name of the cache file
     * @return string relative path
     */
    private function getCacheFileName($name) {
        return implode('/', array(self::$CACHE_DIR, $name));
    }
    
    /**
     * Save the current fingerprint.
     * This has potential for a race condition, when changes where made while 
     * the document was updated.
     * @param string $name name of the cache file
     */
    private function saveFingerprint($name) {
    	if (($name=="collections_meta") || ($name=="all_notebooks")){
    		return;
    	}
    	
    	$fingerprints = $this->loadFingerprints();
    	$fingerprint = $this->getFingerprint();
    	if ($fingerprint== FALSE){
    		throw new Exception(sprintf("could not get data from _changes"));
    	}
    	$fingerprints->{$name} = $fingerprint;
    	$this->writeFingerprints($fingerprints);
    	
    	return TRUE;
    }
    
    /**
     * Get the saved fingerprint.
     * @param string $name name of the cache file
     */
    private function getSavedFingerprint($name) {
    	$fingerprints = $this->loadFingerprints();
    	if (isset($fingerprints->{$name})){
    		return $fingerprints->{$name};
    	}
    	return FALSE;
    }
    
    /**
     * Load the file of all fingerprints.
     * @param string $name name of the cache file
     */
    private function loadFingerprints() {
    	$fingerprintsFile = $this->getCacheFileName(self::$FINGERPRINTS_FILE_NAME);
    	$fHandle = fopen($fingerprintsFile, 'c+');
    	if (!$fHandle) {
    		throw new Exception(sprintf("could not open fingerprint file for reading: '%s'", self::$FINGERPRINTS_FILE_NAME));
    	}
    	$fileSize = filesize($fingerprintsFile);
    	if ($fileSize == 0){
    		$dataFromFile = "{}";
    	} else {
    		$dataFromFile = fread($fHandle, $fileSize);
    	}
    	$decodedData = json_decode($dataFromFile);
    	if ($decodedData === NULL){
    		throw new Exception(sprintf("could not parse fingerprint file: '%s'", self::$FINGERPRINTS_FILE_NAME));
    	}
    	fclose($fHandle);
    	return $decodedData;
    	
    }

    /**
     * Write the file of all fingerprints.
     * @param string $name name of the cache file
     */    
    private function writeFingerprints($fingerprints) {
    	$fingerprintsFile = $this->getCacheFileName(self::$FINGERPRINTS_FILE_NAME);
    	$encodedData = json_encode($fingerprints, JSON_PRETTY_PRINT);
    	if ($encodedData === FALSE){
    		throw new Exception(sprintf("encoding of data failed: '%s'", self::$FINGERPRINTS_FILE_NAME));
    	}
    	$fHandle = fopen($fingerprintsFile, 'w');
    	if (!$fHandle) {
    		throw new Exception(sprintf("could not open fingerprint file for writing: '%s'", self::$FINGERPRINTS_FILE_NAME));
    	}
    	fwrite($fHandle, $encodedData);
    	fclose($fHandle);
    }
    
    /**
     * Get last couch sequence and first doc first rev
     * Which should be unique in (hopefully) all cases
     * @return int
     */
    private function getFingerprint() {
    	$lastSequence = $this->getLastSequence();
    	if ($lastSequence === FALSE){
    		return FALSE;
    	}
    	$firstDocFirstRev = $this->getFirstDocFirstRev();
    	if ($firstDocFirstRev=== FALSE){
    		return FALSE;
    	}
    	$return = new stdClass();
    	$return->{"lastSequence"} = $lastSequence;
    	$return->{"firstDocFirstRev"} = $firstDocFirstRev;
    	return $return;
    }
    
    /**
     * Get last couch sequence (of currently used DB)
     * @return int
     */
    private function getLastSequence() {
    	global $dbInstance;
    	
    	$changes = $dbInstance->fetchDocById("_changes?descending=true&limit=1");
    	if (isset($changes) && isset($changes->body) && isset($changes->body->last_seq)){
    		return $changes->body->last_seq;
    	}
		return FALSE;
    }
    
    /**
     * Get last couch sequence (of currently used DB)
     * @return int
     */
    private function getFirstDocFirstRev() {
    	global $dbInstance;
    	
    	$changes = $dbInstance->fetchDocById("_changes?limit=1");
    	if (isset($changes) && isset($changes->body) && isset($changes->body->results) && isset($changes->body->results[0])){
    		return $changes->body->results[0]->{"changes"}[0]->{"rev"};
    	}
    	return FALSE;
    }
    
    /**
     * Get fingerprint of cache file and compare with current couch fingerprint
     * false equals the need to regenerate the data
     * @param string $name cache file name
     * @return bool
     */
    private function compareFingerprint($name) {
    	if (($name=="collections_meta") || ($name=="all_notebooks")){
    		return TRUE;
    	}

    	$savedFingerprint = $this->getSavedFingerprint($name);
		if ($savedFingerprint== FALSE){
			return FALSE;
		}
		$fingerprint = $this->getFingerprint();
		if ($fingerprint === FALSE){
			return FALSE;
		}
		
		if (($savedFingerprint->{"lastSequence"} == $fingerprint->{"lastSequence"}) && 
			($savedFingerprint->{"firstDocFirstRev"} == $fingerprint->{"firstDocFirstRev"}) ){
			return TRUE;
		}

    	return FALSE;
    }
}