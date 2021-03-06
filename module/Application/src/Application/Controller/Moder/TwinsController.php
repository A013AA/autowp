<?php

namespace Application\Controller\Moder;

use Zend\Form\Form;
use Zend\Mvc\Controller\AbstractActionController;

use Application\Model\Message;

use Twins_Groups;
use Users;

class TwinsController extends AbstractActionController
{
    /**
     * @var Form
     */
    private $editForm;

    /**
     * @var Form
     */
    private $descForm;

    private $textStorage;

    public function __construct($textStorage, Form $editForm, Form $descForm)
    {
        $this->textStorage = $textStorage;
        $this->editForm = $editForm;
        $this->descForm = $descForm;
    }

    /**
     * @param Zend_Db_Table_Row $group
     * @return string
     */
    private function twinsGroupModerUrl($group, $forceCanonical)
    {
        return $this->url()->fromRoute('moder/twins/params', [
            'action'         => 'twins-group',
            'twins_group_id' => $group->id
        ], [
            'force_canonical' => $forceCanonical
        ]);
    }

    public function twinsGroupAction()
    {
        if (!$this->user()->inheritsRole('moder')) {
            return $this->forbiddenAction();
        }

        $table = new Twins_Groups();
        $group = $table->find($this->params('twins_group_id'))->current();
        if (!$group) {
            return $this->notFoundAction();
        }

        $canEdit = $this->user()->isAllowed('twins', 'edit');

        if ($canEdit) {
            $request = $this->getRequest();

            $this->editForm->setData($group->toArray());

            if ($request->isPost()) {
                $this->editForm->setData($this->params()->fromPost());

                if ($this->editForm->isValid()) {
                    $values = $this->editForm->getData();

                    $group->setFromArray([
                        'name' => $values['name'],
                    ]);
                    $group->save();

                    $this->log(sprintf(
                        'Рекдактирование группы близнецов %s',
                        $group->name
                    ), [$group]);

                    return $this->redirect()->toUrl($this->twinsGroupModerUrl($group, true));
                }
            }
        }

        if ($canEdit) {
            $this->descForm->setAttribute('action', $this->url()->fromRoute('moder/twins/params', [
                'action'         => 'save-description',
                'twins_group_id' => $group['id']
            ]));
        }

        if ($group->text_id) {
            $description = $this->textStorage->getText($group->text_id);
            if ($canEdit) {
                $this->descForm->setData([
                    'markdown' => $description
                ]);
            }
        } else {
            $description = '';
        }

        return [
            'group'       => $group,
            'canEdit'     => $canEdit,
            'description' => $description,
            'descForm'    => $this->descForm,
            'editForm'    => $this->editForm,
            'cars'        => $group->findCarsViaTwins_Groups_Cars()
        ];
    }

    public function saveDescriptionAction()
    {
        $canEdit = $this->user()->isAllowed('brand', 'edit');
        if (!$canEdit) {
            return $this->forbiddenAction();
        }

        $user = $this->user()->get();

        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->forbiddenAction();
        }

        $table = new Twins_Groups();
        $group = $table->find($this->params('twins_group_id'))->current();
        if (!$group) {
            return $this->forbiddenAction();
        }

        $this->descForm->setData($this->params()->fromPost());

        if ($this->descForm->isValid()) {

            $values = $this->descForm->getData();

            $text = $values['markdown'];

            if ($group->text_id) {
                $this->textStorage->setText($group->text_id, $text, $user->id);
            } elseif ($text) {
                $textId = $this->textStorage->createText($text, $user->id);
                $group->text_id = $textId;
                $group->save();
            }


            $this->log(sprintf(
                'Редактирование описания группы близнецов %s',
                $group->name
            ), $group);

            if ($group->text_id) {
                $userIds = $this->textStorage->getTextUserIds($group->text_id);
                $message = sprintf(
                    'Пользователь %s редактировал описание группы близнецов %s ( %s )',
                    $this->url()->fromRoute('users/user', [
                        'user_id' => $user->identity ? $user->identity : 'user' . $user->id
                    ]),
                    $group->name,
                    $this->twinsGroupModerUrl($group, true)
                );

                $userTable = new Users();
                $mModel = new Message();
                foreach ($userIds as $userId) {
                    if ($userId != $user->id) {
                        foreach ($userTable->find($userId) as $userRow) {
                            $mModel->send(null, $userRow->id, $message);
                        }
                    }
                }
            }
        }

        return $this->redirect()->toUrl($this->twinsGroupModerUrl($group, true));
    }
}