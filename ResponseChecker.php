<?php

/**
 * Created by IntelliJ IDEA.
 * User: clay
 * Date: 8/23/15
 * Time: 9:49 PM
 */

class ResponseCategory {
    public $frequency;
    public $confidence;

    function __construct($frequency = 0, $confidence = 0.0)
    {
        $this->frequency = $frequency;
        $this->confidence = $confidence;
    }

}

class Response {
    public $email;
    public $username;
    public $ip;

    public function __construct($username = null, $email = null, $ip = null)
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

    public function fromJson($string)
    {
        $response = json_decode($string, true);

        if (isset($response['username'])) {
            $this->username = $this->ResponseCategoryFromAssoc($response['username']);
        }

        if (isset($response['email'])) {
            $this->email = $this->ResponseCategoryFromAssoc($response['email']);
        }

        if (isset($response['ip'])) {
            $this->ip = $this->ResponseCategoryFromAssoc($response['ip']);
        }
    }

    /**
     * @param $assoc
     */
    protected function ResponseCategoryFromAssoc($assoc)
    {
        $frequency = 0;
        $confidence = 0.0;

        if (isset($assoc['frequency'])) {
            $frequency = $assoc['frequency'];
        }
        if (isset($assoc['confidence'])) {
            $confidence = $assoc['confidence'];
        }
        return new ResponseCategory($frequency, $confidence);
    }
}


class ResponseChecker
{

    public $accepted;
    public $confidence;
    public $frequency;
    public $trigger;

    /// Maximum Confidence which a category may have and be considered a valid user
    public $tolerance;

    public function __construct($tolerance = 10.0)
    {
        $this->accepted = true;
        $this->confidence = 0.0;
        $this->trigger = "";

        $this->tolerance = $tolerance;
    }

    /**
     * Check a StopForumSpam.com response, with side effects of populating the object's properties.
     *
     * @param $json Raw JSON input string
     * @return bool
     */
    public function userIsValid($json)
    {
        $response = new Response();
        $response->fromJson($json);
        $result = true;

        if (($result = $this->categoryIsValid($response->username)) === false) {
            $this->trigger = "username";
        } else if (($result = $this->categoryIsValid($response->email)) === false) {
            $this->trigger = "email";
        } else if (($result = $this->categoryIsValid($response->ip)) === false) {
            $this->trigger = "ip";
        }

        $this->accepted = $result;

        return $result;
    }

    /**
     * Check an individual category to see if it represents a valid user.  To be a valid user the category must have
     * occurs of 0 or confidence of less than the global tolerance.
     *
     * *Side Effects:* Populates $confidence if validity check fails.
     *
     * @param ResponseCategory $category
     * @return bool
     */
    private function categoryIsValid(ResponseCategory $category)
    {
        if ($category->frequency > 0 && $category->confidence > $this->tolerance) {
            $this->confidence = $category->confidence;
            $this->frequency = $category->frequency;
            return false;
        }
        return true;
    }


}