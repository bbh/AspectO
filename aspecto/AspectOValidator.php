<?
/**
 * Validates and Aspect object against a ReflectionClass
 *
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2006 Basilio Brice&tilde;o Hern&aacute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.8
 */
class AspectOValidator
{
    public function __construct ( Aspect $aspect ) {
        try {
            try {
                // check pointcuts and its joinpoints
                if ( isset( $aspect->pointcuts ) ) {
                    foreach ( $aspect->pointcuts as $pointcut ) {
                        $class = new ReflectionClass( $pointcut->joinpoint->class );
                        if ( is_array( $pointcut->joinpoint->method ) ) {
                            foreach ( $pointcut->joinpoint->method as $method ) {
                                $reflected_method = $class->getMethod( $method->name );
                            }
                        } else {
                            $reflected_method = $class->getMethod( $pointcut->joinpoint->method->name );
                        }
                    }
                }
                // check advices vs pointcuts
                if ( isset( $aspect->advices ) ) {
                    if ( !isset( $aspect->pointcuts ) || !$aspect->pointcuts ) {
                        throw new AspectOException( 'Advices have been declared, but there are no '
                                                    .'pointcuts in aspect '.$aspect->name );
                    }
                    foreach ( $aspect->advices as $advice ) {
                        foreach ( $aspect->pointcuts as $pointcut ) {
                            if ( is_array( $advice->pointcut ) ) {
                                foreach ( $advice->pointcut as $advice_pointcut ) {
                                    if ( $advice_pointcut == $pointcut->name ) {
                                        $pointcut_array_advice_flag = true;
                                        $pointcut_array_advice[$pointcut->name] = true;
                                    }
                                }
                            } else {
                                if ( $advice->pointcut == $pointcut->name ) {
                                    $pointcut_advice_flag = true;
                                }
                            }
                        }/*
                        ----------------------------------------
                        WARNING! This validation is buggy FIX IT
                        ----------------------------------------
                        if ( isset( $pointcut_array_advice_flag ) && $pointcut_array_advice_flag ) {
                            var_dump( $pointcut_array_advice );
                            var_dump( $advice->pointcut );
                            if ( count( $pointcut_array_advice ) != count( $advice->pointcut ) ) {
                                throw new AspectOException( 'Some '.$advice->type.' advice is '
                                                            .'referencing to multiple pointcuts and'
                                                            .' one of them is inexistent' );
                            }
                        }
                        */
                        if ( !isset( $pointcut_advice_flag ) || !$pointcut_advice_flag ) {
                            throw new AspectOException( 'Some '.$advice->type.' advice is referencing'
                                                        .' to the inexistent pointcut '
                                                        .$advice->pointcut );
                        }
                    }
                }
                // check intertype's properties
                if ( isset( $aspect->intertypes ) && isset( $aspect->intertypes->properties ) ) {
                    if ( is_array( $aspect->intertypes->properties ) ) {
                        foreach ( $aspect->intertypes->properties as $property ) {
                            $class = new ReflectionClass( $property->class );
                            if ( $class->hasProperty( $property->name ) ) {
                                throw new AspectOException( 'Property '
                                                            . $property->name
                                                            . ' already exists in class '
                                                            . $property->class );
                            }
                        }
                    } else {
                        $class = new ReflectionClass( $aspect->intertypes->properties->class );
                        if ( $class->hasProperty( $aspect->intertypes->properties->name ) ) {
                            throw new AspectOException( 'Property '
                                                        . $aspect->intertypes->properties->name
                                                        . ' already exists in class '
                                                        . $aspect->intertypes->properties->class );
                        }
                    }
                }
                // checks intertype's methods
                if ( isset( $aspect->intertypes) && isset( $aspect->intertypes->methods ) ) {
                    if ( is_array( $aspect->intertypes->methods ) ) {
                        foreach ( $aspect->intertypes->methods as $method ) {
                            $class = new ReflectionClass( $method->class );
                            if ( $class->hasMethod( $method->name ) ) {
                                throw new AspectOException( 'Intertype method '
                                                            . $method->name
                                                            . ' exists in class '
                                                            . $method->class );
                            }
                        }
                    } else {
                        $class = new ReflectionClass( $aspect->intertypes->methods->class );
                        if ( $class->hasProperty( $aspect->intertypes->methods->name ) ) {
                            throw new AspectOException( 'Intertype method '
                                                        . $aspect->intertypes->method->name
                                                        . ' exists in class '
                                                        . $aspect->intertypes->method->class );
                        }
                    }
                }
                // checks intertype's inheritance (abstract)
                if ( isset( $aspect->intertypes)
                     && isset( $aspect->intertypes->inheritance->action )
                     && $aspect->intertypes->inheritance->action == 'extends' ) {

                    if ( is_array( $aspect->intertypes->inheritance ) ) {
                        foreach ( $aspect->intertypes->inheritance as $inheritance ) {
                            $class = new ReflectionClass( $inheritance->class );
                            if ( $class->getParentClass() ) {
                                throw new AspectOException( 'Inheritance Intertype in class '
                                                            . $inheritance->class
                                                            . ' already extends another class.' );
                            }
                        }
                    } else {
                        $class = new ReflectionClass( $aspect->intertypes->inheritance->class );
                        if ( $class->getParentClass() ) {
                            throw new AspectOException( 'Inheritance Intertype class '
                                                        . $aspect->intertypes->inheritance->class
                                                        . ' already extends another class.' );
                        }
                    }
                }
                // checks intertype's inheritance (interface)
                if ( isset( $aspect->intertypes)
                     && isset( $aspect->intertypes->inheritance->action )
                     && $aspect->intertypes->inheritance->action == 'implements' ) {
                    if ( is_array( $aspect->intertypes->inheritance ) ) {
                        foreach ( $aspect->intertypes->inheritance as $inheritance ) {
                            $class = new ReflectionClass( $inheritance->class );
                            if ( $class->implementsInterface( $inheritance->parent ) ) {
                                throw new AspectOException( 'Inheritance Intertype class '
                                                           . $inheritance->class
                                                           . ' already implements '
                                                           . $inheritance->parent );
                            }
                        }
                    } else {
                        $class = new ReflectionClass( $aspect->intertypes->inheritance->class );
                        if ( $class->implementsInterface( $aspect->intertypes->inheritance->parent ) ) {
                            throw new AspectOException( 'Inheritance Intertype class '
                                                        . $aspect->intertypes->inheritance->class
                                                        . ' already implements '
                                                        . $aspect->intertypes->inheritance->parent );
                        }
                    }
                }
            } catch ( ReflectionException $e ) {
                throw new AspectOException( $e->getMessage() );
            }
        } catch ( AspectOException $e ) {
            echo $e->__toString(); exit;
        }
    }
}
?>