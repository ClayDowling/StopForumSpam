<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 8/23/15
 * Time: 10:48 PM
 */

require_once("../ResponseChecker.php");





class ResponseCheckerTest extends PHPUnit_Framework_TestCase
{

    public function testPassingReponse()
    {
        $resp = new Response();
        $json = json_encode($resp);

        $checker = new ResponseChecker();

        $this->assertEquals(true, $checker->userIsValid($json));
    }

    public function testToleranceIsSetInConstructor()
    {
        $checker = new ResponseChecker(7.6);

        $this->assertEquals(7.6, $checker->tolerance);
    }

    public function testEmailOccursHighConfidenceFails()
    {
        $resp = new Response(null, new ResponseCategory(10, 25.0), null);
        $json = json_encode($resp);

        $checker = new ResponseChecker();
        $this->assertEquals(false, $checker->userIsValid($json));
        $this->assertEquals("email", $checker->trigger);
        $this->assertEquals(25.0, $checker->confidence);
    }

    public function testEmailOccursLowConfidencePasses()
    {
        $resp = new Response(null, new ResponseCategory(10, 9.7), null);
        $json = json_encode($resp);

        $checker = new ResponseChecker();
        $this->assertEquals(true, $checker->userIsValid($json));
        $this->assertEquals("", $checker->trigger);
        $this->assertEquals(0, $checker->confidence);
    }

    public function testUsernameOccursHighConfidenceFails()
    {
        $resp = new Response(new ResponseCategory(10, 25.0), null, null);
        $json = json_encode($resp);

        $checker = new ResponseChecker();
        $this->assertEquals(false, $checker->userIsValid($json));
        $this->assertEquals("username", $checker->trigger);
        $this->assertEquals(25.0, $checker->confidence);
    }

    public function testUsernameOccursLowConfidencePasses()
    {
        $resp = new Response(new ResponseCategory(10, 9.7), null, null);
        $json = json_encode($resp);

        $checker = new ResponseChecker();
        $this->assertEquals(true, $checker->userIsValid($json));
        $this->assertEquals("", $checker->trigger);
        $this->assertEquals(0, $checker->confidence);
    }

    public function testIpOccursHighConfidenceFails()
    {
        $resp = new Response(null, null, new ResponseCategory(10, 25.0));
        $json = json_encode($resp);

        $checker = new ResponseChecker();
        $this->assertEquals(false, $checker->userIsValid($json));
        $this->assertEquals("ip", $checker->trigger);
        $this->assertEquals(25.0, $checker->confidence);
    }

    public function testIpOccursLowConfidencePasses()
    {
        $resp = new Response(null, null, new ResponseCategory(10, 9.7));
        $json = json_encode($resp);

        $checker = new ResponseChecker();
        $this->assertEquals(true, $checker->userIsValid($json));
        $this->assertEquals("", $checker->trigger);
        $this->assertEquals(0, $checker->confidence);
    }
}

