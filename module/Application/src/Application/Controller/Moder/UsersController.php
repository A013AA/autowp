<?php

namespace Application\Controller\Moder;

use Zend\Mvc\Controller\AbstractActionController;

use Application\Paginator\Adapter\Zend1DbTableSelect;

use Session;
use Users;

class UsersController extends AbstractActionController
{
    /**
     * @var Users
     */
    private $table;

    public function __construct()
    {
        $this->table = new Users();
    }

    public function indexAction()
    {
        if (!$this->user()->inheritsRole('moder')) {
            return $this->forbiddenAction();
        }

        $select = $this->table->select(true)
            ->order('id');

        $paginator = new \Zend\Paginator\Paginator(
            new Zend1DbTableSelect($select)
        );

        $paginator
            ->setItemCountPerPage(30)
            ->setCurrentPageNumber($this->params('page'));

        return [
            'paginator' => $paginator
        ];
    }

    public function removeUserPhotoAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->forbiddenAction();
        }

        $can = $this->user()->isAllowed('user', 'ban');
        if (!$can) {
            return $this->forbiddenAction();
        }

        $row = $this->table->find($this->params('id'))->current();

        if (!$row) {
            return $this->notFoundAction();
        }

        $oldImageId = $row->img;
        if ($oldImageId) {
            $row->img = null;
            $row->save();
            $this->imageStorage()->removeImage($oldImageId);
        }

        $this->log(sprintf(
            'Удаление фотографии пользователя №%s',
            $row->id
        ), [$row]);

        return $this->redirect()->toUrl($this->url()->fromRoute('users/user', [
            'user_id' => $row->identity ? $row->identity : 'user' . $row->id
        ]));
    }

    public function deleteUserAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->forbiddenAction();
        }

        $can = $this->user()->isAllowed('user', 'delete');
        if (!$can) {
            return $this->forbiddenAction();
        }

        $row = $this->table->find($this->params('id'))->current();
        if (!$row) {
            return $this->notFoundAction();
        }

        $oldImageId = $row->img;
        if ($oldImageId) {
            $row->img = null;
            $row->save();
            $this->imageStorage()->removeImage($oldImageId);
        }

        $row->deleted = 1;
        $row->save();

        $sessionTable = new Session();

        $sessionTable->delete([
            'user_id = ?' => $row->id
        ]);

        $this->log(sprintf(
            'Удаление пользователя №%s',
            $row->id
        ), [$row]);

        return $this->redirect()->toUrl($this->url()->fromRoute('users/user', [
            'user_id' => $row->identity ? $row->identity : 'user' . $row->id
        ]));
    }
}