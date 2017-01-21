<?php

namespace Kanboard\Plugin\GitlabWebhook;

use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;
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
        $this->route->addRoute('/webhook/gitlab/:project_id/:token', 'WebhookController', 'handler', 'GitlabWebhook');
        $this->applicationAccessMap->add('WebhookController', 'handler', Role::APP_PUBLIC);
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');

        $this->eventManager->register(WebhookHandler::EVENT_COMMIT, t('GitLab commit received'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_OPENED, t('GitLab issue opened'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_CLOSED, t('GitLab issue closed'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_REOPENED, t('GitLab issue reopened'));
        $this->eventManager->register(WebhookHandler::EVENT_ISSUE_COMMENT, t('GitLab issue comment created'));
    }

    public function getPluginName()
    {
        return 'GitLab Webhook';
    }

    public function getPluginDescription()
    {
        return t('Bind GitLab webhook events to Kanboard automatic actions');
    }

    public function getPluginAuthor()
    {
        return 'Frédéric Guillot';
    }

    public function getPluginVersion()
    {
        return '1.0.5';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/kanboard/plugin-gitlab-webhook';
    }

    public function getCompatibleVersion()
    {
        return '>=1.0.37';
    }
}
