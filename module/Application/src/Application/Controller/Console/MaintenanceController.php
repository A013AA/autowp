<?php

namespace Application\Controller\Console;

use Zend\Console\Console;
use Zend\Mvc\Controller\AbstractActionController;

use Exception;

use Zend_Db_Adapter_Abstract;
use Zend_Paginator;
use Zend_Session;

use Cars;
use Category_Parent;
use Comments;

class MaintenanceController extends AbstractActionController
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    private $db;

    private $sessionConfig;

    public function __construct(Zend_Db_Adapter_Abstract $db, array $sessionConfig)
    {
        $this->db = $db;
        $this->sessionConfig = $sessionConfig;
    }

    public function dumpAction()
    {
        $console = Console::getInstance();

        $config = $this->db->getConfig();

        $destFile = APPLICATION_PATH . '/data/dump/' . date('Y-m-d_H.i.s') . '.dump.sql';

        $console->write('Dumping ... ');

        $cmd = sprintf(
            'mysqldump -u%s -p%s -h%s --set-gtid-purged=OFF %s > %s',
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['host']),
            escapeshellarg($config['dbname']),
            escapeshellarg($destFile)
        );

        exec($cmd);

        if (!file_exists($destFile)) {
            throw new Exception('Error creating dump');
        }

        $console->writeLine('ok');

        $console->write('Gzipping ... ');

        $cmd = sprintf(
            'gzip %s',
            escapeshellarg($destFile)
        );

        exec($cmd);

        $console->writeLine('ok');
    }

    public function clearSessionsAction()
    {
        $console = Console::getInstance();

        $maxlifetime = $this->sessionConfig['gc_maxlifetime'];
        if (!$maxlifetime) {
            throw new Exception('Option session.gc_maxlifetime not found');
        }

        Zend_Session::getSaveHandler()->gc($maxlifetime);

        $console->writeLine("Sessions garabage collected");
    }

    public function rebuildCategoryParentAction()
    {
        $cpTable = new Category_Parent();

        $cpTable->rebuild();

        Console::getInstance()->writeLine("Ok");
    }

    public function rebuildCarOrderCacheAction()
    {
        $console = Console::getInstance();

        $carTable = new Cars();

        $paginator = Zend_Paginator::factory($carTable->select(true)->order('id'))
            ->setItemCountPerPage(100);

        $pagesCount = $paginator->count();
        for ($i=1; $i<=$pagesCount; $i++) {
            $paginator->setCurrentPageNumber($i);
            foreach ($paginator->getCurrentItems() as $carRow) {
                $console->writeLine($carRow->id);
                $carRow->updateOrderCache();
            }
        }

        $console->writeLine("ok");
    }

    public function commentsRepliesCountAction()
    {
        $comments = new Comments();

        $affected = $comments->updateRepliesCount();

        Console::getInstance()->writeLine("ok $affected");
    }

}