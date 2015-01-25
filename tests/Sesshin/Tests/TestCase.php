<?php
namespace Sesshin\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function setPropertyAccessible($object, $property_name)
    {
        $ref_prop = new \ReflectionProperty(get_class($object), $property_name);
        $ref_prop->setAccessible(true);

        return $ref_prop;
    }

    /**
     * @return \ReflectionProperty
     */
    public function setPropertyValue($object, $property_name, $value)
    {
        $ref_prop = $this->setPropertyAccessible($object, $property_name);
        $ref_prop->setValue($object, $value);

        return $ref_prop;
    }

    public function setMethodAccessible($object, $method_name)
    {
        $ref_method = new \ReflectionMethod($object, $method_name);
        $ref_method->setAccessible(true);

        return $ref_method;
    }

    public function invokeMethod($object, $method_name, $args = array())
    {
        $ref_method = $this->setMethodAccessible($object, $method_name);

        return $ref_method->invokeArgs($object, $args);
    }
}
