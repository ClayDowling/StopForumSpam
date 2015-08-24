<?php

/**
 * StopForumSpam plugin
 * User: clay
 * Date: 7/25/15
 * Time: 6:02 PM
 */

require("SpamLogger.php");

class action_plugin_stopforumspam extends DokuWiki_Action_Plugin
{
    private $db = 0;
    public $databasefile = '';
    public $tolerance = 10.0;

    protected $logger;

    public function __construct() {
        DokuWiki_Action_Plugin::__construct();

        global $DOKU_INC;

        $this->loadConfig();
        $this->tolerance = $this->conf['tolerance'];

        $this->logger = new SpamLogger($DOKU_INC . "/data/pages/spamlogger");

        $this->success = true;
    }

    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AUTH_USER_CHANGE', 'BEFORE', $this, "check_spammer_database");
    }

    private function do_check($username, $email, $ip)
    {
        $uri = sprintf("http://api.stopforumspam.org/api?f=json&email=%s&username=%s&ip=%s",
            urlencode($email), urlencode($username), urlencode($ip));
        $json = file_get_contents($uri);

        if ($json === false) {
            return false;
        }
        return json_decode($json);
    }

    public function check_spammer_database(Doku_Event $event, $param)
    {
        $can_modify = true;
        if ($event->data['type'] == 'create') {

            $username = $event->data['params'][0];
            $email = $event->data['params'][3];
            $ip = $_SERVER['REMOTE_ADDR'];
            $response = $this->do_check($username, $email, $ip);
            $trigger = '';

            $status = $this->checkResponse($response);

            if ($can_modify === false) {
                msg('Potentially a spammer', -1);
                $event->preventDefault();
            }
            $this->logger->LogAttempt($username, $email, $ip, $trigger, $confidence, $can_modify);
        }
    }

    /**
     * @param $response
     * @return array
     */
    public function checkResponse($response)
    {
        $spammer = true;
        $trigger = "";
        $confidence = 0;

        if (($spammer = ($response->email->occurs > 0 && $response->email->confidence > $this->tolerance)) == false) {
            $trigger = 'email';
            $confidence = $response->email->confidence;
        }
        else if (($spammer  = ($response->username->occurs > 0 && $response->username->confidence > $this->tolerance)) == false) {
            $trigger = 'username';
            $confidence = $response->username->confidence;
        }
        else if (($spammer = ($response->ip->occurs > 0 && $response->ip->confidence > $this->tolerance)) == false) {
            $trigger = 'ip';
            $confidence = $response->ip->confidence;
        }

        $result["trigger"] = $trigger;
        $result["spammer"] = $spammer;
        $result["confidence"] = $confidence;

        return $result;
    }
}