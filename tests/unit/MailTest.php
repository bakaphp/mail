<?php

class AuthTest extends PhalconUnitTestCase
{

    /**
     * Test userlogin
     *
     * @return boolean
     */
    public function testMail()
    {
        //send email
        $this->_getDI()->get('mail')->to('max@mctekk.com')->subject('testqueue')->content('hola')->send();

    }

    /**
     * this runs before everyone
     */
    protected function setUp()
    {
        $this->_getDI();

    }

    protected function tearDown()
    {
    }

}
