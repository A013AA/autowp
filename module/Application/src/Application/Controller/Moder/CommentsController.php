<?php

namespace Application\Controller\Moder;

use Zend\Form\Form;
use Zend\Mvc\Controller\AbstractActionController;

use Application\Paginator\Adapter\Zend1DbTableSelect;

use Brands;
use Comment_Message;
use Picture;
use Users;

class CommentsController extends AbstractActionController
{
    /**
     * @var Form
     */
    private $form;

    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    public function indexAction()
    {
        if (!$this->user()->inheritsRole('moder')) {
            return $this->forbiddenAction();
        }

        $brandTable = new Brands();

        $brandRows = $brandTable->fetchAll(
            $brandTable->select(true)
                /*->join('brands_cars', 'brands.id = brands_cars.brand_id', null)
                ->join('car_parent_cache', 'brands_cars.car_id = car_parent_cache.parent_id', null)
                ->join('pictures', 'pictures.car_id = car_parent_cache.car_id', null)
                ->where('pictures.type = ?', Picture::CAR_TYPE_ID)
                ->join('comments_messages', 'comments_messages.item_id = pictures.id', null)
                ->where('comments_messages.type_id = ?', Comment_Message::PICTURES_TYPE_ID)
                ->group('brands.id')*/
                ->order(['brands.position', 'brands.caption'])
        );
        $brandOptions = [
            '' => '--'
        ];
        foreach ($brandRows as $brandRow) {
            $brandOptions[$brandRow->id] = $brandRow->caption;
        }

        if ($this->getRequest()->isPost()) {
            $params = $this->params()->fromPost();
            unset($params['submit']);
            foreach ($params as $key => $value) {
                if (strlen($value) <= 0) {
                    $params[$key] = null;
                }
            }
            return $this->redirect()->toUrl($this->url()->fromRoute('moder/comments/params', $params));
        }

        $commentTable = new Comment_Message();

        $select = $commentTable->select(true)
            ->order(['comments_messages.datetime DESC']);

        $this->form->setData($this->params()->fromRoute());

        if ($this->form->isValid()) {
            $values = $this->form->getData();

            if ($values['user']) {

                if (!is_numeric($values['user'])) {
                    $userTable = new Users();
                    $userRow = $userTable->fetchRow([
                        'identity = ?' => $values['user']
                    ]);
                    if ($userRow) {
                        $values['user'] = $userRow->id;
                    }
                }

                $select->where('comments_messages.author_id = ?', $values['user']);
            }

            if (strlen($values['moderator_attention'])) {
                switch ($values['moderator_attention']) {
                    case Comment_Message::MODERATOR_ATTENTION_NONE:
                    case Comment_Message::MODERATOR_ATTENTION_REQUIRED:
                    case Comment_Message::MODERATOR_ATTENTION_COMPLETED:
                        $select->where('comments_messages.moderator_attention = ?', $values['moderator_attention']);
                        break;
                }
            }

            if ($values['brand_id']) {
                $select
                    ->where('comments_messages.type_id = ?', Comment_Message::PICTURES_TYPE_ID)
                    ->join('pictures', 'comments_messages.item_id = pictures.id', null)
                    ->where('pictures.type = ?', Picture::CAR_TYPE_ID)
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->join('brands_cars', 'car_parent_cache.parent_id = brands_cars.car_id', null)
                    ->where('brands_cars.brand_id = ?', $values['brand_id']);
            }

            if ($values['car_id']) {
                $select
                    ->where('comments_messages.type_id = ?', Comment_Message::PICTURES_TYPE_ID)
                    ->join('pictures', 'comments_messages.item_id = pictures.id', null)
                    ->where('pictures.type = ?', Picture::CAR_TYPE_ID)
                    ->join('car_parent_cache', 'pictures.car_id = car_parent_cache.car_id', null)
                    ->where('car_parent_cache.parent_id = ?', $values['car_id']);
            }
        }

        $paginator = new \Zend\Paginator\Paginator(
            new Zend1DbTableSelect($select)
        );

        $paginator
            ->setItemCountPerPage(50)
            ->setCurrentPageNumber($this->params('page'));



        $comments = [];
        foreach ($paginator->getCurrentItems() as $commentRow) {
            $status = '';
            if ($commentRow->type_id == Comment_Message::PICTURES_TYPE_ID) {
                $pictures = $this->catalogue()->getPictureTable();
                $picture = $pictures->find($commentRow->item_id)->current();
                if ($picture) {
                    switch ($picture->status) {
                        case Picture::STATUS_ACCEPTED:
                            $status = '<span class="label label-success">принято</span>';
                            break;
                        case Picture::STATUS_NEW:
                            $status = '<span class="label label-warning">новое</span>';
                            break;
                        case Picture::STATUS_INBOX:
                            $status = '<span class="label label-warning">входящее</span>';
                            break;
                        case Picture::STATUS_REMOVED:
                            $status = '<span class="label label-danger">удалено</span>';
                            break;
                        case Picture::STATUS_REMOVING:
                            $status = '<span class="label label-danger">удаляется</span>';
                            break;
                    }
                }
            }

            $comments[] = [
                'url'     => $commentRow->getUrl(),
                'message' => $commentRow->getMessagePreview(),
                'user'    => $commentRow->findParentUsers(),
                'status'  => $status,
                'new'     => $commentRow->isNew($this->user()->get()->id)
            ];
        }

        return [
            'form'      => $this->form,
            'paginator' => $paginator,
            'comments'  => $comments,
            'urlParams' => $this->params()->fromRoute()
        ];
    }
}
