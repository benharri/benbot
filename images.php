<?php

class SavedImages {
    var $dir;
    var $imgs;

    public function __construct($dirpath = __DIR__.'/uploaded_images/') {
        $dir = new DirectoryIterator($dirpath);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {

                $imgs[$fileinfo->getBasename(".".$dir->getExtension())] = new SavedImage($fileinfo->getBasename(".".$dir->getExtension()));
            }
        }
    }


}


class SavedImage {
    var $name;
    var $filename;
}
