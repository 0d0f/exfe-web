<?php

class XDraftModels extends DataModel
{

    public function delDraft($draft_id)
    {
        return $this->query("DELETE FROM `cross_drafts` WHERE `id` = {$draft_id}");
    }

    public function saveDraft($identity_id, $title, $json, $draft_id = null)
    {
        $json = addslashes($json);
        if ($draft_id) {
            $result = $this->query("UPDATE `cross_drafts` SET `creator_id` = {$identity_id}, `title` = '{$title}', `json` = '{$json}' WHERE `id` = $draft_id");
            return $result ? $draft_id : null;
        } else {
            $result = $this->query("INSERT INTO `cross_drafts` SET `creator_id` = {$identity_id}, `title` = '{$title}', `json` = '{$json}'");
            return $result && isset($result['insert_id']) ? $result['insert_id'] : null;
        }
    }

    public function getDraft($draft_id)
    {
        $draft = $this->getRow("SELECT `json` FROM `cross_drafts` WHERE `id` = {$draft_id}");
        return $draft ? $draft['json'] : null;
    }

}
