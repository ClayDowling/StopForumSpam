<?php

/**
 * StopForumSpam plugin
 * User: clay
 * Date: 7/25/15
 * Time: 6:02 PM
 */
class action_plugin_stopforumspam extends DokuWiki_Action_Plugin
{
    private $db = 0;
    public $databasefile = '';
    public $tolerance = 10.0;

    public function __construct() {
        DokuWiki_Action_Plugin::__construct();

        $testing = getenv("TESTING");
        if ($testing == "") {
            $this->loadConfig();

            $this->tolerance = $this->conf['tolerance'];

        }

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

        $log_message = sprintf("StopForumSpam: username=%s,email=%s,ip=%s,response=%s",
            $username, $email, $ip, $json);
        error_log($log_message);

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

            list($can_modify, $trigger) = $this->checkResponse($response);

            if ($can_modify === false) {
                msg('Potentially a spammer', -1);
                $event->preventDefault();
            }
            $this->log_spam_attempt($username, $email, $ip, $trigger, $confidence, $can_modify);
        }
    }

    protected function check_category($status) {
        if ($status->appears != 0) {
            if ($status->confidence > $this->tolerance) {
                return false;
            }
        }
        return true;
    }

    private function log_spam_attempt($username, $email, $ip, $trigger, $confidence, $accepted)
    {
        $fd = $this->log_file_create();
        $date = new DateTime();
        fputcsv($fd, array($date->format(DateTime::ISO8601), $username, $email, $ip, $trigger, $confidence, $accepted));
        fclose($fd);
    }

    private function log_file_create()
    {
        global $DOKU_INC;
        $datepart = date("%Y-%m");
        $directory = $DOKU_INC . "/data/pages/stopforumspam";
        $filename = sprintf("%s/%s.csv", $directory, $datepart);
        $fd = 0;
        if (file_exists($filename)) {
            mkdir($directory, 0755, true);
            $fd = fopen($filename, "w");
            fputcsv($fd, array(
                "DATE",
                "USERNAME",
                "EMAIL",
                "IP",
                "TRIGGER",
                "CONFIDENCE",
                "ACCEPTED"
            ));
        } else {
            $fd = fopen($filename, "a");
        }

        return $fd;
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