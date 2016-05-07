<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 9/16/15
 * Time: 9:15 PM
 */
class admin_plugin_stopforumspam extends DokuWiki_Admin_Plugin
{
    protected $db;
    protected $logger;

    protected function init()
    {
        $this->logger = new SpamLogger();
        $this->db = $this->logger->OpenLogFile();
    }

    public function handle()
    {

    }

    public function html()
    {
        $this->init();
    }

    public function forAdminOnly()
    {
        return true;
    }

}