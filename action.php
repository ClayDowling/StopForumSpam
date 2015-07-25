<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 7/25/15
 * Time: 6:02 PM
 */
class action_plugin_stopforumspam extends DokuWiki_Action_Plugin
{
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('AUTH_USER_CHANGE', 'BEFORE', $this, "check_spammer_database");
    }

    private function do_check($username, $email, $ip)
    {
        $uri = sprintf("http://api.stopforumspam.org/api?f=json&email=%s&username=%s&ip=%s",
            $email, $username, $ip);
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

            $response = $this->do_check($event->data['params'][0],
                    $event->data['params'][3], $_SERVER['REMOTE_ADDR']);
            if ($response->email->appears != 0) {
                $can_modify = false;
            }
            if ($response->username->appears != 0) {
                if ($response->username->confidence > 10.0) {
                    $can_modify = false;
                }
            }
            if ($response->ip->appears != 0) {
                $can_modify = false;
            }
            if ($can_modify === false) {
                mgs('Potentially a spammer')
                $event->preventDefault();
            }
        }
    }
}