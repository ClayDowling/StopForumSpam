<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 8/23/15
 * Time: 10:48 PM
 */

require_once("../ResponseChecker.php");

class ResponseCategory {
    public $occurs;
    public $confidence;

    public function __create($occures = 0, $confidence = 0.0)
    {
        $this->occurs = $occures;
        $this->confidence = $confidence;
    }
}

class Response {
    public $email;
    public $username;
    public $ip;

    public function __create($username = null, $email = null, $ip = null)
    {
        if ($username == null) {
            $username = new ResponseCategory();
        }
        if ($email == null) {
            $email = new ResponseCategory();
        }
        if ($ip == null) {
            $ip = new ResponseCategory();
        }

        $this->email = $email;
        $this->username = $username;
        $this->ip = $ip;
    }
}


class ResponseCheckerTest extends PHPUnit_Framework_TestCase
{

    public function testPassingReponse()
    {
        $resp = new Response();
        $checker = new ResponseChecker();

        $this->assertEquals(true, $checker->userIsValid($resp));
    }

    public function testEmailOccursHighConfidenceFails()
    {
        $resp = new Response(null, new ResponseCategory(10, 25.0), null);

        $checker = new ResponseChecker();
        $this->assertEquals(false, $checker->userIsValid($resp));
        $this->assertEquals("email", $checker->trigger);
        $this->assertEquals(25.0, $checker->confidence);
    }
}

