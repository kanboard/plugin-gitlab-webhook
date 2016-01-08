<?php

namespace Kanboard\Plugin\GitlabWebhook\Controller;

use Kanboard\Controller\Base;
use Kanboard\Plugin\GitlabWebhook\WebhookHandler;

/**
 * Webhook Controller
 *
 * @package  controller
 * @author   Frederic Guillot
 */
class Webhook extends Base
{
    /**
     * Handle Gitlab webhooks
     *
     * @access public
     */
    public function handler()
    {
        $this->checkWebhookToken();

        $gitlabWebhook = new WebhookHandler($this->container);
        $gitlabWebhook->setProjectId($this->request->getIntegerParam('project_id'));
        $result = $gitlabWebhook->parsePayload($this->request->getJson());

        echo $result ? 'PARSED' : 'IGNORED';
    }
}
