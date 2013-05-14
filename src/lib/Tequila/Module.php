<?php

/**
 * This is the base class for Tequila modules.
 *
 * Tequila modules are classes which provide commands (public methods
 * with name not beginning with an underscore, “_””).
 *
 * Classes inheriting from this class have access to the current
 * Tequila class and can interact with the shell.
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @codeCoverageIgnore Trivial code.
 */
class Tequila_Module
{
    /**
     * Creates an instance of this class.
     *
     * This static method  is invoked to instanciate a new  object of the $class
     * class.
     *
     * This method may be overriden in derivated classes to implements different
     * policies.
     *
     * Note:  The  $class parameter  is  only  present  to permits  the  current
     * implementation to instanciate the  correct class even through inheritance
     * because  PHP  prior to  the  version 5.3  does  not  support late  static
     * bindings.
     *
     * @param  Tequila  $tequila  The   Tequila  instance  which  requested  the
     *                            creation.
     * @param  string   $class    The class to instanciate.
     *
     * @return $class
     */
    static function _factory(Tequila $tequila, $class)
    {
        return new $class($tequila);
    }

    protected function __construct(Tequila $tequila)
    {
        $this->_tequila = $tequila;
    }

    /**
     * @var Tequila
     */
    protected $_tequila;
}
