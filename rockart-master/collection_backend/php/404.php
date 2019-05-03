<?php

    $ORIG_REQ = $_SERVER['REQUEST_URI'];
    $except = array("png", "jpg", "jpeg");
    try {

    if (preg_match('/\.('.implode('|', $except).')$/i', $ORIG_REQ, $matches)) {
        $PATH_TO_DIR = substr($ORIG_REQ, 1, strrpos($ORIG_REQ,"/"));
        $FILE_NOT_FOUND = substr($ORIG_REQ, strrpos($ORIG_REQ,"/")+1, strlen($ORIG_REQ));
        $FILE_NOT_FOUND_WITHOUT_EXT = substr($FILE_NOT_FOUND, 0, strrpos($FILE_NOT_FOUND, "."));   
        $TINY = false;
        $SMALL = false;
        if(strpos( $FILE_NOT_FOUND, "small") !== FALSE){
          $SMALL = true;          
        } else if(strpos( $FILE_NOT_FOUND, "tiny") !== FALSE){
          $TINY = true;
        }        
        if (is_dir($PATH_TO_DIR)){
          if ($dh = opendir($PATH_TO_DIR)){
            $FILE_TO_COPY = false;
            $FILE_TO_RESIZE = false;
            while (($file = readdir($dh)) !== false){
              if (!preg_match('/\.('.implode('|', $except).')$/i', $file, $matches)) {
                continue;
              }                         
              $file_WITHOUT_EXT = substr($file, 0, strrpos($file, "."));    
              if(strpos($FILE_NOT_FOUND_WITHOUT_EXT, $file_WITHOUT_EXT) !== FALSE){               
              // THERE IS A FILE IN FOLDER AS MASTER FOR THUMBS               
                $FILE_TO_RESIZE = true;                
              } 
              // THERE IS SMALL/TINY FILE WHICH CAN BE COPIED
              if(strpos($file, "tiny") !== FALSE and $TINY){
                $FILE_TO_COPY = true;
              } else if(strpos($file, "small") !== FALSE and $SMALL){
                $FILE_TO_COPY = true;
              }
              if($FILE_TO_COPY){
                  copy($PATH_TO_DIR.$file, substr($ORIG_REQ, 1, strlen($ORIG_REQ)));            
              } else if($FILE_TO_RESIZE){     
                  list($width, $height) = getimagesize($PATH_TO_DIR.$file);                                  
                  $newwidth = 0;
                  $newheight = 0;
                  if($TINY){
                    $newwidth = 58;
                    $newheight = 58;
                  } else if($SMALL){
                    $newwidth = 400;
                    $newheight = 300;
                  }
                  $thumb = new Imagick();
                  $thumb->readImage($PATH_TO_DIR.$file);
                  $thumb->resizeImage($newwidth,$newwidth,Imagick::FILTER_LANCZOS,1);
                  $thumb->writeImage(substr($ORIG_REQ, 1, strlen($ORIG_REQ)));
                  $thumb->clear();
                  $thumb->destroy();
                  header("Location: ".$ORIG_REQ );
                  closedir($dh); 
                  die();
               }
            }
            closedir($dh);   
            //no master for thumbs found
            $NEW_PATH = str_replace("Service", "Repos", $PATH_TO_DIR); 
            if (is_dir($NEW_PATH)){
              if ($dh = opendir($NEW_PATH)){
                $except = array("xls", "xlsx");
                while (($file = readdir($dh)) !== false){               
                  if (preg_match('/\.('.implode('|', $except).')$/i', $file, $matches)) {
                    //it's a thumb for xls.... '
                    copy("img/xls.png", substr($ORIG_REQ, 1, strlen($ORIG_REQ)));  
                    closedir($dh);
                    header("Location: ".$ORIG_REQ );
                    die();
                  }
                }
              }
            }
          }
        }        
    }
} catch (Exception $e) {
    
}
printf("<h1 align='center'>HTTP STATUS CODE 404</h1>");
printf("<p><p><hr><p align='center'>Page " . $ORIG_REQ . " not found</p>");


?>