<?php

namespace Application\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

use Picture;

use Zend_Db_Expr;

class PictureVote extends AbstractPlugin
{
    /**
     * @var Picture
     */
    private $table;

    public function __construct()
    {
        $this->table = new Picture();
    }

    private function isLastPicture($picture)
    {
        $result = null;
        if ($picture->type == Picture::CAR_TYPE_ID && $picture->status == Picture::STATUS_ACCEPTED) {
            $car = $picture->findParentCars();
            if ($car) {
                $db = $this->table->getAdapter();
                $result = !$db->fetchOne(
                    $db->select()
                        ->from('pictures', [new Zend_Db_Expr('COUNT(1)')])
                        ->where('car_id = ?', $car->id)
                        ->where('status = ?', Picture::STATUS_ACCEPTED)
                        ->where('id <> ?', $picture->id)
                );
            }
        }

        return $result;
    }

    private function getAcceptedCount($picture)
    {
        $result = null;
        if ($picture->type == Picture::CAR_TYPE_ID) {
            $car = $picture->findParentCars();
            if ($car) {
                $db = $this->table->getAdapter();
                $result = $db->fetchOne(
                    $db->select()
                        ->from('pictures', [new Zend_Db_Expr('COUNT(1)')])
                        ->where('car_id = ?', $car->id)
                        ->where('status = ?', Picture::STATUS_ACCEPTED)
                );
            }
        }
        return $result;
    }

    private function getVoteOptions()
    {
        $voteOptions = [
            'плохое качество',
            'дубль',
            'любительское фото',
            'не по теме сайта',
            'другая',
            'единственное фото',
            'своя',
        ];

        $cookies = $this->getController()->getRequest()->getCookie();

        if (isset($cookies['customReason'])) {
            foreach ((array)unserialize($cookies['customReason']) as $reason) {
                if (strlen($reason) && !in_array($reason, $voteOptions)) {
                    $voteOptions[] = $reason;
                }
            }
        }

        return array_combine($voteOptions, $voteOptions);
    }

    public function __invoke($pictureId, $options)
    {
        $options = array_replace([
            'hideVote' => false
        ], $options);

        $picture = $this->table->find($pictureId)->current();
        if (!$picture) {
            return false;
        }

        $controller = $this->getController();

        if (!$controller->user()->inheritsRole('moder')) {
            return false;
        }

        $user = $controller->user()->get();
        $voteExists = $this->pictureVoteExists($picture, $user);

        return [
            'isLastPicture'     => $this->isLastPicture($picture),
            'acceptedCount'     => $this->getAcceptedCount($picture),
            'canDelete'         => $this->pictureCanDelete($picture),
            'canVote'           => !$voteExists && $controller->user()->isAllowed('picture', 'moder_vote'),
            'voteExists'        => $voteExists,
            'moderVotes'        => $options['hideVote'] ? null : $picture->findPictures_Moder_Votes(),
            'pictureDeleteUrl'  => $controller->url()->fromRoute('moder/pictures/params', [
                'action'     => 'delete-picture',
                'picture_id' => $picture->id
            ]),
            'pictureUnvoteUrl'  => $controller->url()->fromRoute('moder/pictures/params', [
                'action'     => 'picture-vote',
                'form'       => 'picture-unvote',
                'picture_id' => $picture->id
            ]),
            'pictureVoteUrl'    => $controller->url()->fromRoute('moder/pictures/params', [
                'action'     => 'picture-vote',
                'form'       => 'picture-vote',
                'picture_id' => $picture->id
            ]),
            'voteOptions' => $this->getVoteOptions()
        ];
    }

    private function pictureCanDelete($picture)
    {
        $user = $this->getController()->user()->get();
        $canDelete = false;
        if (in_array($picture->status, [Picture::STATUS_INBOX, Picture::STATUS_NEW])) {
            if ($this->getController()->user()->isAllowed('picture', 'remove')) {
                if ($this->pictureVoteExists($picture, $user)) {
                    $canDelete = true;
                }
            } elseif ($this->getController()->user()->isAllowed('picture', 'remove_by_vote')) {
                if ($this->pictureVoteExists($picture, $user)) {
                    $db = $this->table->getAdapter();
                    $acceptVotes = (int)$db->fetchOne(
                        $db->select()
                            ->from('pictures_moder_votes', array(new Zend_Db_Expr('COUNT(1)')))
                            ->where('picture_id = ?', $picture->id)
                            ->where('vote > 0')
                    );
                    $deleteVotes = (int)$db->fetchOne(
                        $db->select()
                            ->from('pictures_moder_votes', array(new Zend_Db_Expr('COUNT(1)')))
                            ->where('picture_id = ?', $picture->id)
                            ->where('vote = 0')
                    );

                    $canDelete = ($deleteVotes > $acceptVotes);
                }
            }
        }

        return $canDelete;
    }

    private function pictureVoteExists($picture, $user)
    {
        $db = $this->table->getAdapter();
        return $db->fetchOne(
            $db->select()
                ->from('pictures_moder_votes', new Zend_Db_Expr('COUNT(1)'))
                ->where('picture_id = ?', $picture->id)
                ->where('user_id = ?', $user->id)
        );
    }
}
