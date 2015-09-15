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

    private $spamLogger;
    private $db;

    protected function setUp()
    {
        $this->spamLogger = new SpamLogger("/tmp");
        $this->setupForImport();
        $this->db = $this->spamLogger->OpenLogFile();
    }

    protected function setupForImport()
    {
        $contents = <<<FILE1
DATE,USERNAME,EMAIL,IP,TRIGGER,CONFIDENCE,ACCEPTED
2015-09-01T00:00:48-0400,PiperL3190506387,piper.sleath@3mail.2waky.com,72.52.91.30,ip,99.55,rejected
2015-09-01T01:33:15-0400,DiegoFrancisco0,elfinhysteria57c@aol.com,198.52.182.130,,0,accepted
FILE1;
        file_put_contents("/tmp/StopForumSpam-1.csv", $contents);

        $contents = <<<FILE2
DATE,USERNAME,EMAIL,IP,TRIGGER,CONFIDENCE,ACCEPTED
2015-09-01T00:03:01-0400,AntoniettaHargis,antonietta_hargis58@7mail.jungleheart.com,89.105.194.70,ip,98.6,rejected
2015-09-01T00:06:57-0400,LoreneEusebio6,incandescentritha@nokiamail.com,213.184.105.132,email,0.02,rejected
2015-09-01T00:10:23-0400,PeggyBoudreaux,kennethwootton9012@0815.su,83.143.242.78,email,99.95,rejected
FILE2;
        file_put_contents("/tmp/StopForumSpam-2.csv", $contents);

        if (file_exists("/tmp/StopForumSpam.db")) {
            unlink("/tmp/StopForumSpam.db");
        }

        if (file_exists("/tmp/StopForumSpam-archive.zip")) {
            unlink("/tmp/StopForumSpam-archive.zip");
        }
    }

    public function testLogFileIsCurrentYearMonth()
    {
        $expected = "StopForumSpam.db";

        $this->assertEquals($expected, $this->spamLogger->logfile);
    }

    public function testNewLogFileHasCorrectColumns()
    {
        $columns[] = array(
            'time_attempted' => 0,
            'username' => 0,
            'email' => 0,
            'ip' => 0,
            'trigger' => 0,
            'confidence' => 0,
            'accepted' => 0,
            'verified' => 0
        );

        $result = $this->db->query("PRAGMA table_info(account_attempt)");
        while ($row = $result->fetchArray(SQLITE3_NUM)) {
            $columns[$row[1]] = 1;
        }

        $this->assertEquals(1, $columns['time_attempted']);
        $this->assertEquals(1, $columns['username']);
        $this->assertEquals(1, $columns['email']);
        $this->assertEquals(1, $columns['ip']);
        $this->assertEquals(1, $columns['trigger']);
        $this->assertEquals(1, $columns['confidence']);
        $this->assertEquals(1, $columns['accepted']);
        $this->assertEquals(1, $columns['verified']);

    }

    public function testLoggedRecordCanBeReadCorrectly()
    {
        $this->db->exec("DELETE FROM account_attempt");

        $this->spamLogger->LogAttempt("bogus", "bogus@fake.org", "192.168.1.1", "test", 11.0, false);

        $result = $this->db->query("SELECT * FROM account_attempt WHERE username='bogus'");

        $row = $result->fetchArray();

        $this->assertEquals("bogus", $row['username']);
        $this->assertEquals("bogus@fake.org", $row['email']);
        $this->assertEquals("192.168.1.1", $row['ip']);
        $this->assertEquals("test", $row['trigger']);
        $this->assertEquals("11.0", $row['confidence']);
        $this->assertEquals("N", $row['accepted']);

    }

    public function testOldLogsAreImported()
    {
        $this->spamLogger->LoadCSV();

        $result = $this->db->query("SELECT COUNT(username) FROM account_attempt");
        $row = $result->fetchArray();
        $this->assertEquals(5, $row[0]);
    }

    /**
     * @depends testOldLogsAreImported
     */
    public function test_WhenLoadCsvIsCalled_WithCsvFilesPresent_ArchiveFileIsCreated()
    {
        $this->spamLogger->LoadCSV();

        $archivefile = $this->spamLogger->logdir . "/StopForumSpam-archive.zip";
        $this->assertTrue(file_exists($archivefile), "Archive file " . $archivefile ." not present");
    }

    public function test_WhenLoadCsvIsCalled_WithCsvFilesPresent_ArchiveFileContainsFileNamesInArchiveFolder()
    {
        $this->spamLogger->LoadCSV();

        $archivefile = $this->spamLogger->logdir . "/StopForumSpam-archive.zip";
        $zip = new ZipArchive();
        $zip->open($archivefile);
        $this->assertEquals("archive/StopForumSpam-1.csv", $zip->getNameIndex(1));
        $this->assertEquals("archive/StopForumSpam-2.csv", $zip->getNameIndex(2));
        $zip->close();
    }
}
