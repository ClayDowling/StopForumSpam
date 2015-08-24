<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 8/23/15
 * Time: 9:49 PM
 */
class ResponseChecker
{

    public $accepted;
    public $confidence;
    public $trigger;

    public function userIsValid($response)
    {
        return true;
    }
}