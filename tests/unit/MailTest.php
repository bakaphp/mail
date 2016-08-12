<?php

class AuthTest extends PhalconUnitTestCase
{

    /**
     * Test normal email
     *
     * @return boolean
     */
    public function testSimpleEmail()
    {
        //send email
        $this->_getDI()->get('mail')
            ->to('max@mctekk.com')
            ->subject('Test Normal Email queue')
            ->content('normal email send via queue')
            ->send();
    }

    /**
     * Test html email
     *
     * @return boolean
     */
    public function testTemplateMail()
    {
        //send email
        $this->_getDI()->get('mail')
            ->to('max@mctekk.com')
            ->subject('Test Template Email queue')
            ->params(['name' => 'dfad'])
            ->template('email.volt') //you can also use template() default template is email.volt
            ->send();
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
