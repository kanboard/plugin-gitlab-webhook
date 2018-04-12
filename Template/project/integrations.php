<h3><i class="fa fa-gitlab fa-fw" aria-hidden="true"></i><?= t('GitLab webhooks') ?></h3>
<div class="panel">
    <input type="text" class="auto-select" readonly="readonly" value="<?= $this->url->href('WebhookController', 'handler', array('plugin' => 'GitlabWebhook', 'token' => $webhook_token, 'project_id' => $project['id']), false, '', true) ?>"/><br/>
    <p class="form-help"><a href="https://github.com/kanboard/plugin-gitlab-webhook#documentation" target="_blank"><?= t('Help on GitLab webhooks') ?></a></p>
</div>
