<?php
use PHPUnit\Framework\TestCase;

class ClassG2WPIAjaxTest extends TestCase
{
    public function test_class_exists()
    {
        $this->assertTrue(class_exists('G2WPI_Ajax'));
    }
}
