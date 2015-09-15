<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 9/5/15
 * Time: 11:44 AM
 *
 * Interface to authentication backend
 */
class Auth
{

    public function setup()
    {
        global $conf;
        global $plugin_controller;
        $auth = false;

        // This does not work for pre weatherwax plugins, but the user has already received
        // an abundance of warnings about deprecated authentication types
        foreach($plugin_controller->getList('auth') as $plugin) {
            if ($conf['authtype'] === $plugin) {
                $auth = $plugin_controller->load('auth', $plugin);
                break;
            }
        }

        return $auth;
    }

}