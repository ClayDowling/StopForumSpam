<?php

/**
 * StopForumSpam plugin
 * User: clay
 * Date: 7/25/15
 * Time: 6:02 PM
 */

require("SpamLogger.php");
require("ResponseChecker.php");

class action_plugin_stopforumspam extends DokuWiki_Action_Plugin
{
    private $db = 0;
    public $databasefile = '';
    public $tolerance = 10.0;

    protected $logger;
    protected $checker;

    public function __construct() {

        $this->loadConfig();
        $this->tolerance = $this->conf['tolerance'];

        $logpath = dirname(DOKU_CONF) . "/data/spamlogger";

        $this->logger = new SpamLogger($logpath);
        $this->checker = new ResponseChecker($this->tolerance);

        $this->success = true;
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AUTH_USER_CHANGE', 'BEFORE', $this, "check_spammer_database");
        $controller->register_hook('DISCUSSION_SPAM_CHECK', 'BEFORE', $this, "check_comment_spam");
    }

    private function do_check($username, $email, $ip)
    {
        $uri = sprintf("http://api.stopforumspam.org/api?f=json&email=%s&username=%s&ip=%s",
            urlencode($email), urlencode($username), urlencode($ip));
        $json = file_get_contents($uri);
        return $json;
    }

    public function is_a_spammer($username, $email, $ip) {
        $spammer = false;
        $response = $this->do_check($username, $email, $ip);
        if ($this->checker->userIsValid($response) === false) {
            msg('Potentially a spammer', -1);
            $spammer = true;
        }
        $this->logger->LogAttempt($username, $email, $ip, $this->checker->trigger,
            $this->checker->confidence, $this->checker->accepted);

        return $spammer;
    }

    public function check_spammer_database(Doku_Event $event, $param)
    {
        if ($event->data['type'] == 'create') {

            $username = $event->data['params'][0];
            $email = $event->data['params'][3];
            $ip = $_SERVER['REMOTE_ADDR'];

            if ($this->is_a_spammer($username, $email, $ip)) {
                $event->preventDefault();
            }
        }
    }

    public function check_comment_spam(Doku_Event $event, $comment) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $username = '';
        $email = '';

        if (isset($comment['user'])) {
            $user = $comment['user'];
            if (isset($user['name'])) {
                $username = $user['name'];
            }
            if (isset($user['mail'])) {
                $email = $user['mail'];
            }
        }

        if ($this->is_a_spammer($username, $email, $ip)) {
            return true;
        } else {
            return false;
        }

    }
}