<?php

namespace Application\View\Helper;

use Zend\View\Helper\AbstractHtmlElement;

use Comment_Message;
use Picture;

class ModerMenu extends AbstractHtmlElement
{
    public function __invoke()
    {
        $items = [];

        if ($this->view->user()->inheritsRole('moder')) {

            $pTable = new Picture();
            $inboxCount = $pTable->getAdapter()->fetchOne(
                $pTable->getAdapter()->select()
                    ->from($pTable->info('name'), 'count(1)')
                    ->where('status = ?', Picture::STATUS_INBOX)
            );

            $items[] = [
                'href'  => '/moder/pictures/index/order/1/status/inbox',
                'label' => $this->view->translate('moder-menu/inbox'),
                'count' => $inboxCount,
                'icon'  => 'fa fa-th'
            ];

            $cmTable = new Comment_Message();
            $attentionCount = $cmTable->getAdapter()->fetchOne(
                $cmTable->getAdapter()->select()
                    ->from($cmTable->info('name'), 'count(1)')
                    ->where('moderator_attention = ?', Comment_Message::MODERATOR_ATTENTION_REQUIRED)
            );

            $items[] = [
                'href'  => $this->view->url('moder/comments/params', [
                    'action'              => 'index',
                    'moderator_attention' => Comment_Message::MODERATOR_ATTENTION_REQUIRED
                ]),
                'label' => $this->view->page(110)->name,
                'count' => $attentionCount,
                'icon'  => 'fa fa-comment'
            ];

            if ($this->view->user()->inheritsRole('pages-moder')) {
                $items[] = [
                    'href'  => $this->view->url('moder/pages'),
                    'label' => $this->view->page(68)->name,
                    'icon'  => 'fa fa-book'
                ];
            }

            $items[] = [
                'href'  => $this->view->url('moder/cars'),
                'label' => $this->view->page(131)->name,
                'icon'  => 'fa fa-car'
            ];
        }

        return $this->view->partial('application/moder-menu', [
            'items' => $items
        ]);
    }
}