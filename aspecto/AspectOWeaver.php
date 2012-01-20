<?
/**
 * Weaves Aspects with Classes
 *
 * <code>
 * $weaver = new AspectOWeaver( ApectOConfig $config, Aspect $aspect );
 * </code>
 *
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2007 Basilio Brice&ntilde;o Hern&aacute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.35
 */
class AspectOWeaver
{
    public function __construct( AspectOConfig &$config, Aspect &$aspect )
    {
        foreach ( $aspect->getClasses() as $class_name => $times ) {

            $aspects[$class_name] = self::getAspectByClass( $aspect, $class_name );
        }

        if ( isset( $aspects ) ) {

            foreach ( $aspects as $class_name => $_aspect ) {

              if ( $weave = $this->Weave( $class_name, $_aspect ) ) {

                file_put_contents( $config->getWeavedClassesPath().
                                   $class_name.'.php', $weave->getString() );
              }
            }
        }
    }

    /**
     * Returns an Aspect array sorted by Class
     *
     * @param Aspect $aspect
     * @param String $class
     * @return Array
     */
    final private static function getAspectByClass ( Aspect $aspect, $class ) {
        $aspectByClass = new Aspect();
        $aspectByClass->name = $aspect->getName();
        // Intertypes
        if ( isset( $aspect->intertypes ) && $aspect->intertypes ) {
            // Properties
            if ( isset( $aspect->intertypes->properties ) && $aspect->intertypes->properties ) {
                $aspectByClass->intertypes->properties = self::getObjectByClass( $aspect->intertypes->properties,
                                                                                 $class,
                                                                                 'properties' );
            }
            // Methods
            if ( isset( $aspect->intertypes->methods ) ) {
                $aspectByClass->intertypes->methods = self::getObjectByClass( $aspect->intertypes->methods,
                                                                                 $class,
                                                                                 'methods' );
            }
            // Inheritance
            if ( isset( $aspect->intertypes->inheritance ) ) {
                $aspectByClass->intertypes->inheritance = self::getObjectByClass( $aspect->intertypes->inheritance,
                                                                                  $class,
                                                                                  'inheritance' );
            }
        } // Pointcuts
        if ( isset( $aspect->pointcuts ) ) {
            if ( is_array( $aspect->pointcuts ) ) {
                foreach ( $aspect->pointcuts as $pointcut ) {
                    if ( $pointcut->joinpoint->getClass() == $class ) {
                        $aspectByClass->pointcuts[] = $pointcut;
                    }
                }
            } else {
                if ( $aspect->pointcuts->joinpoint->getClass() == $class ) {
                    $aspectByClass->pointcuts = $aspect->pointcuts;
                }
            }
            if ( isset( $aspectByClass->pointcuts ) ) {
                $aspectByClass->pointcuts = AspectOUtils::FindUniqueArray( $aspectByClass->pointcuts );
            }
        } // Advices
        if ( isset( $aspect->advices ) ) {
            if ( is_array( $aspect->advices ) ) {
                for ( $i = 0; $i < count( $aspect->advices ); $i++ ) {
                    if ( is_array( $aspect->advices[$i]->pointcut ) ) {
                        for ( $i_p = 0; $i_p < count( $aspect->advices[$i]->pointcut ); $i_p++ ) {
                            if ( $advice_by_class = self::getAdvicesByClass( $aspect->advices[$i]->pointcut[$i_p],
                                                                              $aspectByClass->pointcuts,
                                                                              $aspect->advices[$i] ) ) {
                                $aspectByClass->advices[] = $advice_by_class;
                            }
                        }
                    } else {
                        if ( $advice_by_class = self::getAdvicesByClass( $aspect->advices[$i]->pointcut,
                                                                          $aspectByClass->pointcuts,
                                                                          $aspect->advices[$i] ) ) {
                            $aspectByClass->advices[] = $advice_by_class;
                        }
                    }
                }
            } else {
                if ( $advice_by_class = self::getAdvicesByClass( $aspect->advices->pointcut,
                                                                  $aspectByClass->pointcuts,
                                                                  $aspect->advices ) ) {
                    $aspectByClass->advices = $advice_by_class;
                }
            }
            if ( isset( $aspectByClass->advices ) ) {
                $aspectByClass->advices = AspectOUtils::FindUniqueArray( $aspectByClass->advices );
            }
        }
        return $aspectByClass;
    }
    /**
     * Weaves intertypes and calls WeaveMethods to weave pointcuts with advices
     *
     * @param String $class_name
     * @param Aspect $aspect
     * @return AspectOClassParser
     */
    final private function Weave ( $class_name, Aspect $aspect )
    {
        $class = new AspectOClassParser( $class_name );

        // Intertypes
        if ( isset( $aspect->intertypes ) ) {

            // Properties
            if ( isset( $aspect->intertypes->properties ) ) {
                $properties = $aspect->intertypes->properties;
                if ( is_array( $properties ) ) {
                    foreach ( $properties as $property ) {
                        unset( $property->class );
                        $class->addProperty( $property );
                    }
                } elseif ( $properties && $properties != ' ' ) {
                    unset( $aspect->intertypes->properties->class );
                    $class->addProperty( $aspect->intertypes->properties );
                }
            }

            // Methods
            if ( isset( $aspect->intertypes->methods ) ) {
                $methods = $aspect->intertypes->methods;
                if ( is_array( $methods ) ) {
                    foreach ( $methods as $method ) {
                        unset( $method->class );
                        $class->addMethod( $method );
                    }
                } elseif ( is_object( $methods ) ) {
                    unset( $aspect->intertypes->methods->class );
                    $class->addMethod( $aspect->intertypes->methods );
                }
            }

            // Inheritance
            if ( isset( $aspect->intertypes->inheritance ) ) {
                $inheritance = $aspect->intertypes->inheritance;
                if ( is_array( $inheritance ) ) {
                    foreach ( $inheritance as $inh ) {
                        unset( $inh->class );
                        $class->addInheritance( $inh );
                    }
                } elseif ( $inheritance && $inheritance != ' ' ) {
                    unset( $aspect->intertypes->inheritance->class );
                    $class->addInheritance( $aspect->intertypes->inheritance );
                }
            }
        }

        // Pointcuts and Advices
        return self::WeaveMethods( $class, self::getMethodsToWeave( $aspect, $class ) );
    }

    /**
     * Weaves Pointcuts and its Joinpoint with Advices and sets its results into an
     * AspectOClassParser Object
     *
     * @param AspectOClassParser $class
     * @param Mixed $methods
     * @return AspectOClassParser
     */
    final protected static function WeaveMethods ( AspectOClassParser &$class, $method )
    {
        if ( is_array( $method ) ) {

            //foreach ( $methods as $method ) {

              if ( isset( $method['advice_type'] ) && !is_null( $method['advice_type'] ) ) {

                switch ( $method['advice_type'] ) {
                    case 'before' :
                        switch ( $method['pointcut_type'] ) {
                            case 'execution' :
                                $class->setMethodCode( $method['method']->name,
                                                       $method['advice_code'].
                                                       $method['method']->code );
                                break;
                            case 'call' :
                                $temp_method = clone $method['method'];
                                $temp_method->name = $temp_method->name.'_CALL';
                                $class->addMethod( $temp_method );
                                unset( $temp_method );
                                $class->setMethodCode( $method['method']->name,
                                                       $method['advice_code']."\n".'$this->'.
                                                       $method['method']->name.'_CALL('.
                                                       $method['method']->arguments.')' );
                                break;
                            case 'new' :
                                $class->setMethodCode( $method['method']->name,
                                                       $method['advice_code'].
                                                       $method['method']->code );
                                break;
                        }
                        break;
                    case 'after' :
                        switch ( $method['pointcut_type'] ) {
                            case 'execution' :
                                $class->setMethodCode( $method['method']->name,
                                                       $method['method']->code.
                                                       $method['advice_code'] );
                                break;
                            case 'call' :
                                $temp_method = clone $method['method'];
                                $temp_method->name = $temp_method->name.'_CALL';
                                $class->addMethod( $temp_method );
                                unset( $temp_method );
                                $class->setMethodCode( $method['method']->name,
                                                       "\n".'$this->'.$method['method']->name.
                                                       '_CALL('.$method['method']->arguments.");\n".
                                                       $method['advice_code'] );
                                break;
                            case 'new' :
                                $class->setMethodCode( $method['method']->name,
                                                       $method['method']->code.
                                                       $method['advice_code'] );
                                break;
                        }
                        break;
                    case 'around' :
                        switch ( $method['pointcut_type'] ) {
                            case 'execution' :
                                $class->setMethodCode( $method['method']->name,
                                                       preg_replace( '/(proceed\(\s*\);)/',
                                                                     $method['method']->code,
                                                                     $method['advice_code'] ) );
                                break;
                            case 'call' :
                                $temp_method = clone $method['method'];
                                $temp_method->name = $method['method']->name.'_AROUND';
                                $class->addMethod( $temp_method );
                                unset( $temp_method );
                                $class->setMethodCode( $method['method']->name,
                                                       preg_replace( '/(proceed\(\s*\);)/',
                                                                     "\n".'$this->'.
                                                                     $method['method']->name.
                                                                     '_AROUND('.
                                                                     $method['method']->arguments.
                                                                     ");\n",
                                                                     $method['advice_code'] ) );
                                break;
                            case 'new' :
                                $class->setMethodCode( $method['method']->name,
                                                       preg_replace( '/(proceed\(\s*\);)/',
                                                                     $method['method']->code,
                                                                     $method['advice_code'] ) );
                                break;
                        }
                        break;
                }
              }
            //}
        } else {
            return false;
        }
        return $class;
    }
    /**
     * Returns an Array sorted by Methods
     *
     * @param Aspect $aspect
     * @param AspectOClassParser $class
     * @return Array
     */
    final protected static function getMethodsToWeave ( Aspect $aspect, AspectOClassParser $class ) {
        if ( is_array( $aspect->pointcuts ) ) {
            foreach ( $aspect->pointcuts as $pointcut ) {
                if ( is_array( $pointcut->joinpoint->method ) ) {
                    foreach ( $pointcut->joinpoint->method as $method ) {
                        $_methods = self::getMethodFromClass( $class, $method->name );
                        $response[] = self::getArrayOfMethodToWeave( $_methods, $pointcut,
                                                                     $aspect->advices );
                    }
                } else {
                    $_methods = self::getMethodFromClass( $class,
                                                          $pointcut->joinpoint->method->name );
                    $response[] = self::getArrayOfMethodToWeave( $_methods, $pointcut,
                                                                 $aspect->advices );
                }
            }
        } else {
          if ( isset( $aspect->pointcuts->joinpoint->method ) && !is_null( $aspect->pointcuts->joinpoint->method ) ) {
            if ( is_array( $aspect->pointcuts->joinpoint->method ) ) {
                foreach ( $aspect->pointcuts->joinpoint->method as $method ) {
                    $_methods = self::getMethodFromClass( $class, $method->name );
                    $response[] = self::getArrayOfMethodToWeave( $_methods, $aspect->pointcuts,
                                                                 $aspect->advices );
                }
            } else {
                $_methods = self::getMethodFromClass( $class,
                                                      $aspect->pointcuts->joinpoint->method->name );
                $response[] = self::getArrayOfMethodToWeave( $_methods, $aspect->pointcuts,
                                                             $aspect->advices );
            }
          }
        }
        if ( isset( $response ) ) {
            return AspectOUtils::FindUniqueArray( $response );
        }
    }
    /**
     * Returns an Array of Methods with a list of actions for weaving (pointcuts and advices)
     *
     * @param Mixed $methods
     * @param Mixed $pointcut
     * @param Mixed $advices
     * @return Array
     */
    final protected static function getArrayOfMethodToWeave ( $methods, $pointcut, $advices ) {
        if ( $advice_from_pointcut = self::getAdviceFromPointcut( $advices, $pointcut->name ) ) {
            return array_merge( array( 'method' => $methods ),
                                array( 'pointcut_name' => $pointcut->name,
                                       'pointcut_type' => $pointcut->type ),
                                       $advice_from_pointcut );
        }
    }
    /**
     * Checks relations between Pointcuts and Advices and returns an Array of verified advices.
     *
     * @param Mixed $advices
     * @param Mixed $pointcut_name
     * @return Array
     */
    final protected static function getAdviceFromPointcut ( $advices, $pointcut_name ) {
        if ( is_array( $advices ) ) {
            foreach ( $advices as $advice ) {
                if ( is_array( $advice->pointcut ) ) {
                    foreach ( $advice->pointcut as $pointcut ) {
                        if ( $pointcut === $pointcut_name ) {
                            return array( 'advice_type' => $advice->type,
                                          'advice_code' => $advice->code );
                        }
                    }
                } else {
                    if ( $advice->pointcut === $pointcut_name ) {
                        return array( 'advice_type' => $advice->type,
                                      'advice_code' => $advice->code );
                    }
                }
            }
        } else {
          if ( !is_null( $advices ) ) {
            if ( is_array( $advices->pointcut ) ) {
                foreach ( $advices->pointcut as $pointcut ) {
                    if ( $pointcut === $pointcut_name ) {
                        return array( 'advice_type' => $advices->type,
                                      'advice_code' => $advices->code );
                    }
                }
            } else {
                if ( $advices->pointcut === $pointcut_name ) {
                    return array( 'advice_type' => $advices->type,
                                  'advice_code' => $advices->code );
                }
            }
          }
        }
    }
    /**
     * Verifies if a method exists and returns it like a Method Object
     *
     * @param AspectOClassParser $class
     * @param String $method_name
     * @return Method
     */
    final protected static function getMethodFromClass ( AspectOClassParser $class, $method_name )
    {
        if ( is_array( $class->methods ) ) {
            foreach ( $class->methods as $method_from_class ) {
                if ( $method_name === $method_from_class->name ) {
                    return $method_from_class;
                }
            }
        } else {
            if ( $method_name === $class->methods->name ) {
                return $class->methods;
            }
        }
        return false;
    }
    /**
     * Returns an object based in a class and an aspect's node
     *
     * @param Mixed $aspect_node
     * @param String $class
     * @param String $element
     * @return Mixed
     */
    final protected static function getObjectByClass ( $aspect_node, $class, $element )
    {   $response = $response_interior = '';
        if ( is_array( $aspect_node ) ) {
            foreach ( $aspect_node as $item ) {
                if ( $item->getClass() == $class ) {
                    $response_interior[] = $item;
                }
            }
        } else {
            if ( $aspect_node->getClass() == $class ) {
                $response_interior = $aspect_node;
            }
        }
        $response[$element] = $response_interior;
        return AspectOUtils::FindUniqueArray( $response[$element] );
    }
    /**
     * Returns advices sorted by class
     *
     * @param Mixed $pointcut_in_advice
     * @param Mixed $pointcuts_in_class
     * @param Advice $advice
     * @return Mixed
     */
    final protected static function getAdvicesByClass ( $pointcut_in_advice, $pointcuts_in_class, $advice  )
    {
        if ( is_array( $pointcuts_in_class ) ) {
            foreach ( $pointcuts_in_class as $pointcut ) {
                if ( $pointcut_in_advice === $pointcut->name ) {
                    $response[] = $advice;
                }
            }
        } else {
          if ( !is_null( $pointcuts_in_class ) ) {
            if ( $pointcut_in_advice === $pointcuts_in_class->name ) {
                $response[] = $advice;
            }
          }
        }
        if ( isset( $response ) ) {
            return AspectOUtils::FindUniqueArray( $response );
        }
        return false;
    }
}
?>
