<?php

namespace Kanboard\Plugin\GitlabWebhook;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Translator;

class Plugin extends Base
{
    public function initialize()
    {
        $this->actionManager->getAction('\Kanboard\Action\CommentCreation')->addEvent(WebhookHandler::EVENT_ISSUE_COMMENT);
        $this->actionManager->getAction('\Kanboard\Action\CommentCreation')->addEvent(WebhookHandler::EVENT_COMMIT);
        $this->actionManager->getAction('\Kanboard\Action\TaskClose')->addEvent(WebhookHandler::EVENT_COMMIT);
        $this->actionManager->getAction('\Kanboard\Action\TaskClose')->addEvent(WebhookHandler::EVENT_ISSUE_CLOSED);
        $this->actionManager->getAction('\Kanboard\Action\TaskCreation')->addEvent(WebhookHandler::EVENT_ISSUE_OPENED);
        $this->actionManager->getAction('\Kanboard\Action\TaskOpen')->addEvent(WebhookHandler::EVENT_ISSUE_REOPENED);

        $this->template->hook->attach('template:project:integrations', 'GitlabWebhook:project/integrations');

        $this->route->addRoute('/webhook/gitlab/:project_id/:token', 'webhook', 'handler', 'GitlabWebhook');
    }

    public function onStartup()
    {
        Translator::load($this->language->getCurrentLanguage(), __DIR__.'/Locale');

        $this->eventManager->register(WebhookHandler::EVENT_COMMIT, t('Gitlab commit received'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_OPENED, t('Gitlab issue opened'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_CLOSED, t('Gitlab issue closed'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_REOPENED, t('Gitlab issue reopened'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_COMMENT, t('Gitlab issue comment created'));
    }

    public function getPluginName()
    {
        return 'Gitlab Webhook';
    }

    public function getPluginDescription()
    {
        return t('Bind Gitlab webhook events to Kanboard automatic actions');
    }

    public function getPluginAuthor()
    {
        return 'Frédéric Guillot';
    }

    public function getPluginVersion()
    {
        return '1.0.3';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/plugin-gitlab-webhook';
    }
}
