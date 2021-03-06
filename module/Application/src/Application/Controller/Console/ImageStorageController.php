<?php

namespace Application\Controller\Console;

use Zend\Console\Console;
use Zend\Mvc\Controller\AbstractActionController;

use Exception;

class ImageStorageController extends AbstractActionController
{
    public function clearEmptyDirsAction()
    {
        $dirname = $this->params('dirname');

        Console::getInstance()->writeLine("Clear `$dirname`");
        $dir = $this->imageStorage()->getDir($dirname);
        if (!$dir) {
            throw new Exception("Dir '$dirname' not found");
        }

        $this->_recursiveDirectory(realpath($dir->getPath()));

        Console::getInstance()->writeLine("done");
    }

    private function _recursiveDirectory($dir)
    {
        $stack[] = $dir;

        while ($stack) {
            $currentDir = array_pop($stack);

            if ($dh = opendir($currentDir)){
                $count = 0;
                while (($file = readdir($dh)) !== false) {
                    if ($file !== '.' AND $file !== '..') {
                        $count++;
                        $currentFile = $currentDir . DIRECTORY_SEPARATOR . $file;
                        if (is_dir($currentFile)) {
                            $stack[] = $currentFile;
                        }
                    }
                }

                if ($count <= 0) {
                    Console::getInstance()->writeLine($currentDir . ' - empty');
                    rmdir($currentDir);
                }
            }
        }
    }
}