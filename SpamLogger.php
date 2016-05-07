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
        $this->logfile = "StopForumSpam.db";
    }

    public function LogAttempt($username, $email, $ip, $trigger, $confidence, $accepted)
    {
        $db = $this->OpenLogFile();
        $accepted_value = $accepted ? "Y" : "N";

        $sql = <<<ISQL
INSERT INTO account_attempt (username, email, ip, trigger, confidence, accepted)
VALUES (:username, :email, :ip, :trigger, :confidence, :accepted)
ISQL;
        $query = $db->prepare($sql);
        $query->bindParam(":username", $username);
        $query->bindParam(":email", $email);
        $query->bindParam(":ip", $ip);
        $query->bindParam(":trigger", $trigger);
        $query->bindParam(":confidence", $confidence);
        $query->bindParam(":accepted", $accepted_value);
        $query->execute();

    }

    public function OpenLogFile()
    {
        $db = null;
        $filename = $this->logdir . "/" . $this->logfile;

        if (!file_exists($this->logdir)) {
            mkdir($this->logdir, 0755, true);
        }
        $db = new SQLite3($filename);

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS account_attempt (
  time_attempted date not null default CURENT_TIMESTAMP,
  username text not null default '',
  email text not null default '',
  ip text not null default '',
  trigger text not null default '',
  confidence float not null default 0.0,
  accepted text default 'Y',
  verified text default 'N',
  primary key (time_attempted, username, email, ip)
)
SQL;
        $db->exec($sql);

        $sql = <<<IDXSQL
CREATE INDEX IF NOT EXISTS account_verification
ON account_attempt (accepted, verified)
IDXSQL;
        $db->exec($sql);

        return $db;
    }

    public function LoadCSV() {
        $potentialfiles = scandir($this->logdir);
        $csvfiles = array();

        foreach($potentialfiles as $filename) {
            if (strrchr($filename, '.') == ".csv" && strncmp("StopForumSpam-", $filename, 14) == 0) {
                $this->ImportOldFile($filename);
                $csvfiles[] = $filename;
            }
        }

        if (count($csvfiles) > 0) {
            $this->ArchiveFiles($csvfiles);
        }

    }

    private function ArchiveFiles($files)
    {
        $zipfile = new ZipArchive();
        $zipfile->open($this->logdir . "/StopForumSpam-archive.zip", ZipArchive::CREATE);

        $zipfile->addEmptyDir("archive");
        foreach($files as $file) {
            $csvfile = $this->logdir . '/' . $file;
            $zipfile->addFile($csvfile, "archive/" . $file);
        }

        $zipfile->close();

        // NOTE: Cannot delete these files until after closing the ZipArchive
        foreach($files as $file) {
            $csvfile = $this->logdir . '/' . $file;
            unlink($csvfile);
        }

    }

    private function ImportOldFile($filename)
    {
        $db = $this->OpenLogFile();
        $sql = <<<IMPORTSQL
INSERT INTO account_attempt (time_attempted, username, email, ip, trigger, confidence, accepted)
VALUES (:timeattempted, :username, :email, :ip, :trigger, :confidence, :accepted)
IMPORTSQL;

        $query = $db->prepare($sql);
        $count = 0;

        $srcfile = $this->logdir . "/" . $filename;
        $fd = fopen($srcfile, "r");

        $db->exec("BEGIN");
        while(!feof($fd)) {
            $line = fgetcsv($fd);
            if ($line[0] != "DATE") {
                $time_attempted = $line[0];
                $username = $line[1];
                $email = $line[2];
                $ip = $line[3];
                $trigger = $line[4];
                $confidence = $line[5];
                $accepted = "accepted" === $line[6] ? "Y" : "N";

                $query->bindParam(":timeattempted", $time_attempted);
                $query->bindParam(":username", $username);
                $query->bindParam(":email", $email);
                $query->bindParam(":ip", $ip);
                $query->bindParam(":trigger", $trigger);
                $query->bindParam(":confidence", $confidence);
                $query->bindParam(":accepted", $accepted);

                $query->execute();
                $count++;

                if ($count === 300) {
                    $db->exec("COMMIT");
                    $count = 0;
                }
            }
        }
        $db->exec("COMMIT");
    }

    public function Browse($offset)
    {
        return array();
    }
}