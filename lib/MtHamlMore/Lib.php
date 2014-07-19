<?php

namespace MtHamlMore;

use MtHamlMore\Exception\MoreException;

class Lib
{
    /*
     * parse array composed of file path str or glob str
     * notice : no check duplicate
     */
    static function  parseFiles(array $files,$checkExists=true)
    {

       $new_files=array();
       foreach($files as $fileOrDir){
           if( strpos($fileOrDir,'*')!==false){
               $new_files = array_merge( $new_files, glob($fileOrDir));
           }
           elseif($checkExists ){
            if(is_file($fileOrDir))
                $new_files[]=$fileOrDir;
            else
                throw new MoreException("file no exists $fileOrDir");
           }else
               $new_files[]=$fileOrDir;

       }

        return $new_files;
    }
}