<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 8/23/15
 * Time: 6:04 PM
 */
class SpamLogger
{
    public $logfile;
    public $logdir;

    public function __construct($logdir)
    {
        $this->logdir = $logdir;
        $this->logfile = "StopForumSpam-" . date("Y-m") . ".csv";
    }

    public function LogAttempt($username, $email, $ip, $trigger, $confidence, $accepted)
    {
        $fd = $this->OpenLogFile();
        $dt = new DateTime();

        fputcsv($fd, array(
            $dt->format(DateTime::ISO8601),
            $username,
            $email,
            $ip,
            $trigger,
            $confidence,
            $accepted ? "accepted" : "rejected"
        ));

    }

    private function OpenLogFile()
    {
        $filename = $this->logdir . "/" . $this->logfile;
        if (file_exists($filename)) {
            $fd = fopen($filename, "a");
        } else {
            if (!file_exists($this->logdir)) {
                mkdir($this->logdir, 0755, true);
            }
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
        }
        return $fd;
    }
}