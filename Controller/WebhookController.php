<?php

namespace Kanboard\Plugin\GitlabWebhook\Controller;

use Kanboard\Controller\BaseController;
use Kanboard\Plugin\GitlabWebhook\WebhookHandler;

/**
 * Webhook Controller
 *
 * @package  controller
 * @author   Frederic Guillot
 */
class WebhookController extends BaseController
{
    /**
     * Handle GitLab webhooks
     *
     * @access public
     */
    public function handler()
    {
        $this->checkWebhookToken();

        $gitlabWebhook = new WebhookHandler($this->container);
        $gitlabWebhook->setProjectId($this->request->getIntegerParam('project_id'));
        $result = $gitlabWebhook->parsePayload($this->request->getJson());

        $this->response->text($result ? 'PARSED' : 'IGNORED');
    }
}
