<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 8/23/15
 * Time: 6:01 PM
 */

require("../SpamLogger.php");

class LoggerTest extends PHPUnit_Framework_TestCase
{

    public function testLogFileIsCurrentYearMonth()
    {
        $logger = new SpamLogger("/tmp");

        $expected = "StopForumSpam-" . date("Y-m") . ".csv";

        $this->assertEquals($expected, $logger->logfile);
    }

    public function testNewLogFileHasColumnHeaders()
    {
        $logger = new SpamLogger("/tmp");

        $logfile = $logger->logdir . "/" . $logger->logfile;
        $logger->LogAttempt("bogus", "bogus@fake.org", "192.168.1.1", "test", 11.0, false);

        $contents = file($logfile);
        $this->assertEquals("DATE,USERNAME,EMAIL,IP,TRIGGER,CONFIDENCE,ACCEPTED", trim($contents[0]));
    }

    public function testLoggedRecordCanBeReadAsCSV()
    {
        $logger = new SpamLogger("/tmp");
        $logfile = $logger->logdir . "/" . $logger->logfile;
        if (file_exists($logfile)) {
            unlink($logfile);
        }
        $logger->LogAttempt("bogus", "bogus@fake.org", "192.168.1.1", "test", 11.0, false);

        $fd = fopen($logfile, "r");
        $line = fgetcsv($fd);
        $line = fgetcsv($fd);

        $this->assertEquals("bogus", $line[1]);
        $this->assertEquals("bogus@fake.org", $line[2]);
        $this->assertEquals("192.168.1.1", $line[3]);
        $this->assertEquals("test", $line[4]);
        $this->assertEquals("11.0", $line[5]);
        $this->assertEquals("rejected", $line[6]);

    }
}
