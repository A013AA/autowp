<?php
class Comment_Message_Row extends Project_Db_Table_Row
{
    public function getUrl($absolute = false)
    {
        switch ($this->type_id)
        {
            case Comment_Message::PICTURES_TYPE_ID:
                $pictures = new Picture();
                $picture = $pictures->find($this->item_id)->current();
                if ($picture) {
                    return ($absolute ? HOST : '/').'picture/'.($picture->identity ? $picture->identity : $picture->id);
                } else {
                    return false;
                }

            case Comment_Message::VOTINGS_TYPE_ID:
                return ($absolute ? HOST : '/').'voting/voting/id/'.(int)$this->item_id.'/';

            case Comment_Message::TWINS_TYPE_ID:
                return ($absolute ? HOST : '/').'twins/group'.(int)$this->item_id.'/';

            case Comment_Message::ARTICLES_TYPE_ID:
                $articles = new Articles();
                $article = $articles->find($this->item_id)->current();
                return $article->getUrl($absolute);

            case Comment_Message::FORUMS_TYPE_ID:
                return ($absolute ? HOST : '/').'forums/topic-message/message_id/'.(int)$this->id;

            case Comment_Message::MUSEUMS_TYPE_ID:
                return ($absolute ? HOST : '/').'museums/museum/id/'.(int)$this->item_id;
        }
        return null;
    }

    public function getMessagePreview()
    {
        $Msg = str_replace("\r", '', $this->message);
        $Msg = explode("\n", $Msg);
        $Msg = trim($Msg[0]);
        if (mb_strlen($Msg) > 60)
            $Msg = mb_substr($Msg, 0, 60).'...';
        return $Msg;
    }

    public function updateVote()
    {
        $voteTable = new Comment_Vote();

        $this->vote = $voteTable->getAdapter()->fetchOne(
            $voteTable->getAdapter()->select()
                ->from($voteTable->info('name'), new Zend_Db_Expr('sum(vote)'))
                ->where('comment_id = ?', $this->id)
        );
        $this->save();
    }

    public function isNew($userId)
    {
        $db = $this->getTable()->getAdapter();

        $viewTime = $db->fetchRow(
            $db->select()
                ->from('comment_topic_view', 'timestamp')
                ->where('comment_topic_view.item_id = ?', $this->item_id)
                ->where('comment_topic_view.type_id = ?', $this->type_id)
                ->where('comment_topic_view.user_id = ?', $userId)
        );

        return $viewTime ? $this->datetime > $viewTime : true;
    }
}