<?php

namespace Kanboard\Plugin\GitlabWebhook;

use Kanboard\Core\Base;
use Kanboard\Event\GenericEvent;

/**
 * GitLab Webhook
 *
 * @author   Frederic Guillot
 */
class WebhookHandler extends Base
{
    /**
     * Events
     *
     * @var string
     */
    const EVENT_ISSUE_OPENED           = 'gitlab.webhook.issue.opened';
    const EVENT_ISSUE_CLOSED           = 'gitlab.webhook.issue.closed';
    const EVENT_ISSUE_REOPENED         = 'gitlab.webhook.issue.reopened';
    const EVENT_COMMIT                 = 'gitlab.webhook.commit';
    const EVENT_ISSUE_COMMENT          = 'gitlab.webhook.issue.commented';

    /**
     * Supported webhook events
     *
     * @var string
     */
    const TYPE_PUSH    = 'push';
    const TYPE_ISSUE   = 'issue';
    const TYPE_COMMENT = 'comment';

    /**
     * Project id
     *
     * @access private
     * @var integer
     */
    private $project_id = 0;

    /**
     * Set the project id
     *
     * @access public
     * @param  integer   $project_id   Project id
     */
    public function setProjectId($project_id)
    {
        $this->project_id = $project_id;
    }

    /**
     * Parse events
     *
     * @access public
     * @param  array   $payload   GitLab event
     * @return boolean
     */
    public function parsePayload(array $payload)
    {
        switch ($this->getType($payload)) {
            case self::TYPE_PUSH:
                return $this->handlePushEvent($payload);
            case self::TYPE_ISSUE;
                return $this->handleIssueEvent($payload);
            case self::TYPE_COMMENT;
                return $this->handleCommentEvent($payload);
        }

        return false;
    }

    /**
     * Get event type
     *
     * @access public
     * @param  array   $payload   GitLab event
     * @return string
     */
    public function getType(array $payload)
    {
        if (empty($payload['object_kind'])) {
            return '';
        }

        switch ($payload['object_kind']) {
            case 'issue':
                return self::TYPE_ISSUE;
            case 'note':
                return self::TYPE_COMMENT;
            case 'push':
                return self::TYPE_PUSH;
            default:
                return '';
        }
    }

    /**
     * Parse push event
     *
     * @access public
     * @param  array   $payload   GitLab event
     * @return boolean
     */
    public function handlePushEvent(array $payload)
    {
        foreach ($payload['commits'] as $commit) {
            $this->handleCommit($commit);
        }

        return true;
    }

    /**
     * Parse commit
     *
     * @access public
     * @param  array   $commit   GitLab commit
     * @return boolean
     */
    public function handleCommit(array $commit)
    {
        $task_id = $this->taskModel->getTaskIdFromText($commit['message']);

        if (empty($task_id)) {
            return false;
        }

        $task = $this->taskFinderModel->getById($task_id);

        if (empty($task)) {
            return false;
        }

        if ($task['project_id'] != $this->project_id) {
            return false;
        }

        $this->dispatcher->dispatch(
            self::EVENT_COMMIT,
            new GenericEvent(array(
                'task_id' => $task_id,
                'commit_message' => $commit['message'],
                'commit_url' => $commit['url'],
                'comment' => $commit['message']."\n\n[".t('Commit made by @%s on GitLab', $commit['author']['name']).']('.$commit['url'].')'
            ) + $task)
        );

        return true;
    }

    /**
     * Parse issue event
     *
     * @access public
     * @param  array   $payload   GitLab event
     * @return boolean
     */
    public function handleIssueEvent(array $payload)
    {
        switch ($payload['object_attributes']['action']) {
            case 'open':
                return $this->handleIssueOpened($payload['object_attributes'], $payload['project']);
            case 'close':
                return $this->handleIssueClosed($payload['object_attributes']);
            case 'reopen':
                return $this->handleIssueReopened($payload['object_attributes']);
        }

        return false;
    }

    /**
     * Handle new issues
     *
     * @access public
     * @param  array    $issue   Issue data
     * @param  array    $project Project info
     * @return boolean
     */
    public function handleIssueOpened(array $issue, array $project)
    {
        $description = $this->processMessage($issue['description'], $project);
        $description .= "\n\n[".t('GitLab Issue').']('.$issue['url'].')';

        $event = array(
            'project_id' => $this->project_id,
            'reference' => $issue['id'],
            'title' => $issue['title'],
            'description' => $description,
            'assignee_id' => $issue['assignee_id'],
            'author_id' => $issue['author_id'],
        );

        $this->dispatcher->dispatch(
            self::EVENT_ISSUE_OPENED,
            new GenericEvent($event)
        );

        return true;
    }

    /**
     * Handle issue reopening
     *
     * @access public
     * @param  array    $issue   Issue data
     * @return boolean
     */
    public function handleIssueReopened(array $issue)
    {
        $task = $this->taskFinderModel->getByReference($this->project_id, $issue['id']);

        if (! empty($task)) {
            $event = array(
                'project_id' => $this->project_id,
                'task_id' => $task['id'],
                'reference' => $issue['id'],
                'assignee_id' => $issue['assignee_id'],
                'author_id' => $issue['author_id'],
            );

            $this->dispatcher->dispatch(
                self::EVENT_ISSUE_REOPENED,
                new GenericEvent($event)
            );

            return true;
        }

        return false;
    }


    /**
     * Handle issue closing
     *
     * @access public
     * @param  array    $issue   Issue data
     * @return boolean
     */
    public function handleIssueClosed(array $issue)
    {
        $task = $this->taskFinderModel->getByReference($this->project_id, $issue['id']);

        if (! empty($task)) {
            $event = array(
                'project_id' => $this->project_id,
                'task_id' => $task['id'],
                'reference' => $issue['id'],
                'assignee_id' => $issue['assignee_id'],
                'author_id' => $issue['author_id'],
            );

            $this->dispatcher->dispatch(
                self::EVENT_ISSUE_CLOSED,
                new GenericEvent($event)
            );

            return true;
        }

        return false;
    }

    /**
     * Parse comment issue events
     *
     * @access public
     * @param  array   $payload   Event data
     * @return boolean
     */
    public function handleCommentEvent(array $payload)
    {
        if (! isset($payload['issue'])) {
            return false;
        }

        $task = $this->taskFinderModel->getByReference($this->project_id, $payload['issue']['id']);

        if (! empty($task)) {
            $user = $this->userModel->getByUsername($payload['user']['username']);

            if (! empty($user) && ! $this->projectPermissionModel->isAssignable($this->project_id, $user['id'])) {
                $user = array();
            }

            $comment = $this->processMessage($payload['object_attributes']['note'], $payload['project']);
            $comment .= "\n\n[".t('By @%s on GitLab', $payload['user']['username']).']('.$payload['object_attributes']['url'].')';

            $event = array(
                'project_id' => $this->project_id,
                'reference' => $payload['object_attributes']['id'],
                'comment' => $comment,
                'user_id' => ! empty($user) ? $user['id'] : 0,
                'task_id' => $task['id'],
            );

            $this->dispatcher->dispatch(
                self::EVENT_ISSUE_COMMENT,
                new GenericEvent($event)
            );

            return true;
        }

        return false;
    }

    /**
     * Post processing for message content
     *
     * @access public
     * @param  string $message
     * @param  array  $project
     * @return string
     */
    public function processMessage($message, array $project)
    {
        return preg_replace('/\[(.*?)\]\((.+?)\)/', sprintf('[$1](%s$2)', $project['web_url']), $message);
    }
}
