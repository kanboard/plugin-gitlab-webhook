<h3><img src="<?= $this->url->dir() ?>plugins/GitlabWebhook/gitlab-icon.png"/>&nbsp;<?= t('Gitlab webhooks') ?></h3>
<div class="listing">
<input type="text" class="auto-select" readonly="readonly" value="<?= $this->url->href('webhook', 'handler', array('plugin' => 'GitlabWebhook', 'token' => $webhook_token, 'project_id' => $project['id']), false, '', true) ?>"/><br/>
<p class="form-help"><a href="https://kanboard.net/plugin/gitlab-webhook" target="_blank"><?= t('Help on Gitlab webhooks') ?></a></p>
</div>
