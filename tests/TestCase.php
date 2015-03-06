<?php
namespace League\Sesshin\Tests;

class TestCase extends \PHPUnit_Framework_TestCase
{
    public function setPropertyAccessible($object, $propertyName)
    {
        $ref_prop = new \ReflectionProperty(get_class($object), $propertyName);
        $ref_prop->setAccessible(true);

        return $ref_prop;
    }

    /**
     * @return \ReflectionProperty
     */
    public function setPropertyValue($object, $propertyName, $value)
    {
        $ref_prop = $this->setPropertyAccessible($object, $propertyName);
        $ref_prop->setValue($object, $value);

        return $ref_prop;
    }

    public function setMethodAccessible($object, $methodName)
    {
        $ref_method = new \ReflectionMethod($object, $methodName);
        $ref_method->setAccessible(true);

        return $ref_method;
    }

    public function invokeMethod($object, $methodName, $args = array())
    {
        $ref_method = $this->setMethodAccessible($object, $methodName);

        return $ref_method->invokeArgs($object, $args);
    }
}
