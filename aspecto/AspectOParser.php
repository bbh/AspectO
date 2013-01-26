<?php
/**
 * Reads and parses an aspect file and returns an well formed Aspect object
 *
 * <code>
 * $aspect = new AspectOParser( 'AspectFile.php', $AspectOConfigObject );
 * print_r( $aspect->getAspect() );
 * </code>
 *
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2006 Basilio Brice&ntilde;o Hern&aacute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.35
 * @todo add intertype constants
 * @todo add support to readings and writings of attributes (setters and getters)
 * @todo add support to exceptions catching
 * @todo add support to destructors
 * @todo add support to thisJoinPoint reflective object
 */
class AspectOParser extends Overload
{
    public $aspect;
    public $config;
    public function __construct( $aspect, AspectOConfig $config ) {
        $this->setConfig( $config );
        try {
            if ( !file_exists( $aspect ) ) {
                throw new AspectOException( AspectOException::FILE_NOT_EXISTENT );
            }
            $this->Parse( file_get_contents( $aspect ) );
        } catch ( AspectOException $e ) {
            echo $e->__toString(); exit;
        }
    }
    /**
     * Parses aspect code and returns an Aspect's object
     *
     * @param String $aspectFile
     * @return Aspect
     */
    protected function Parse ( $aspectFile ) {
        $aspectFile = AspectOUtils::RemoveComments( $aspectFile );
        if ( preg_match( '/\<\?(php)?\s*aspect\s*(\w*)\s*\{(.*)\}(?=\s*\?\>\Z)/xsU', $aspectFile,
                         $aspect_array ) ) {
            $aspect = new Aspect();
            $aspect->setName( $aspect_array[2] );
            $aspect->setIntertypes( self::ParseIntertypes( $aspect_array[3] ) );
            $aspect->setPointcuts( self::ParsePointCuts( $aspect_array[3] ) );
            $aspect->setAdvices( self::ParseAdvices( $aspect_array[3] ) );
            // unset empty properties
            if ( !$aspect->intertypes ) { unset( $aspect->intertypes ); }
            if ( !$aspect->pointcuts ) { unset( $aspect->pointcuts ); }
            if ( !$aspect->advices ) { unset( $aspect->advices ); }
            $this->aspect = $aspect;
            // set classes declared in aspect
            $this->FindClasses( $aspect );
        } else {
            throw new AspectOException( 'Aspect syntax error' );
        }
    }
    /**
     * Parses aspect's advices and returns one or more Advice's objects
     *
     * @param String $code
     * @return Advice
     */
    protected function ParseAdvices ( $code ) {
        $response = '';
        preg_match_all( '/(before|after|around)\s*\:?\s*([\w\s\|]*)\s*\{(.*)\}'
                        .'(?=\s*(?:before|after|around)|\Z)/sU', $code, $advices );
        for ( $i = 0; $i < count( $advices[0] ); $i++ ) {
            $advice = new Advice();
            // type
            $advice->setType( $advices[1][$i] );
            // pointcut
            if ( strstr( $advices[2][$i], '||' ) ) {
                $advice->setPointcut( preg_split( '/\/\//', str_replace( ' ', '', $advices[2][$i] ) ) );
                //$advice->setPointcut( split( '\|\|', str_replace( ' ', '', $advices[2][$i] ) ) );
            } else {
                $advice->setPointcut( str_replace( ' ', '', $advices[2][$i] ) );
            }
            // code
            if ( $advices[3][$i] ) {
                $advice->setCode( $advices[3][$i] );
            } else {
                unset( $advice->code );
            }
            // set response
            $response[$i] = $advice;
        }
        return $response;
    }
    /**
     * Parses aspect's code and returns one or more Intertype's objects
     *
     * @param String $code
     * @return Intertype
     */
    protected function ParseIntertypes ( $code ) {
        static $flag = 0;
        $intertypes = new Intertype();
        $intertypes->setMethods( $this->ParseIntertypesMethods( $code ) );
        $intertypes->setProperties( $this->ParseIntertypesProperties( $code ) );
        $intertypes->setInheritance( $this->ParseIntertypesInheritance( $code ) );
        // unset empty properties
        if ( !$intertypes->methods ) { unset( $intertypes->methods ); $flag++; }
        if ( !$intertypes->properties ) { unset( $intertypes->properties ); $flag++; }
        if ( !$intertypes->inheritance ) { unset( $intertypes->inheritance ); $flag++; }
        if ( $flag == 3 ) { return false; }
        return $intertypes;
    }
    /**
     * Parses aspect's code and return one or more Method's Intertype's objects
     *
     * @param String $code
     * @return Method
     */
    protected function ParseIntertypesMethods ( $code ) {
        $response_array = $response = '';
        $flag = false;
        $regex = '/((?:final)*)\s*(public|private|protected)\s*((?:static)*)\s*([\w*]*)\s*(\w*)\s*'
               . '\((.*)\)\s*\{(.*)\}'
               . '(?=\s*(?:final|public|private|protected|before|after|around|pointcut|declare)|'
               . '\s*\}?\s*\?\>|\s*\}\Z)/sUx';
        preg_match_all( $regex, $code, $methods );
        for ( $i = 0; $i < count( $methods[0] ); $i++ ) {
            // look for wildcards
            if ( preg_match( '/([\w*]*)([^\w]+)(?(2)([\w*]*))/', $methods[4][$i] ) ) {
                $classes = glob( $this->config->getClassesPath() . '*.php' );
                for ( $ic = 0; $ic < count( $classes ); $ic++ ) {
                    $method = new Method();
                    preg_match( '/\/?(\w*)\.php/', $classes[$ic], $class_name );
                    // wildcard is *
                    if ( $methods[4][$i] == '*' ) {
                        $flag = true;
                    // wildcard contains * (*ex*amp*le*)
                    } elseif ( preg_match( '/('.str_replace('*','.*',$methods[4][$i]).')/',
                                           $class_name[1] ) ) {
                        $flag = true;
                    }
                    if ( $flag ) {
                        $method->setClass( $class_name[1] );
                        $method->setName( $methods[5][$i] );
                        if ( $methods[1][$i] ) {
                            $method->setFinal( true );
                        } else {
                            unset( $method->final );
                        }
                        $method->setVisibility( $methods[2][$i] );
                        if ( $methods[3][$i] ) {
                            $method->setStatic( true );
                        } else {
                            unset( $method->static );
                        }
                        $method->setArguments( str_replace( ' ', '', $methods[6][$i] ) );
                        if ( $methods[7][$i] ) {
                            $method->setCode( $methods[7][$i] );
                        } else {
                            unset( $method->code );
                        }
                        $response_array[$ic] = $method;
                        $flag = false;
                    }
                }
                $response[$i] = AspectOUtils::FindUniqueArray( $response_array );
            // no wildcards
            } else {
                $method = new Method();
                $method->setClass( $methods[4][$i] );
                $method->setName( $methods[5][$i] );
                if ( $methods[1][$i] ) {
                    $method->setFinal( true );
                } else {
                    unset( $method->final );
                }
                $method->setVisibility( $methods[2][$i] );
                if ( $methods[3][$i] ) {
                    $method->setStatic( true );
                } else {
                    unset( $method->static );
                }
                $method->setArguments( str_replace( ' ', '', $methods[6][$i] ) );
                if ( $methods[7][$i] ) {
                    $method->setCode( $methods[7][$i] );
                } else {
                    unset( $method->code );
                }
                $response[$i] = $method;
            }
        }
        return AspectOUtils::FindUniqueArray( $response );
    }
    /**
     * Parses aspect's code and return one or more Property's Intertype's objects
     *
     * @param String $code
     * @return Property
     */
    protected function ParseIntertypesProperties ( $code ) {
        $response = $response_array = ''; $flag = false;
        preg_match_all( '/(public|private|protected)\s*((?:static)?)\s*([\w*]*)\s*:{0,2}\s*\$(\w*)'
                        .'\s*\=?\s*(.*);/', $code, $properties );
        for ( $i = 0; $i < count( $properties[0] ); $i++ ) {
            // look for wildcards
            if ( preg_match( '/([\w*]*)([^\w]+)(?(2)([\w*]*))/', $properties[3][$i] ) ) {
                $classes = glob( $this->config->getClassesPath() . '*.php' );
                for ( $ic = 0; $ic < count( $classes ); $ic++ ) {
                    $property = new Property();
                    preg_match( '/\/?(\w*)\.php/', $classes[$ic], $class_name );
                    // check class
                    if ( class_exists( $class_name[1] ) ) {
                        // wildcard is *
                        if ( $properties[3][$i] == '*' ) {
                            $flag = true;
                        // wildcard contains * (*ex*amp*le*)
                        } elseif ( preg_match('/('.str_replace('*','.*',$properties[3][$i]).')/',
                                   $class_name[1]) ) {
                            $flag = true;
                        }
                        if ( $flag ) {
                            $property->setClass( $class_name[1] );
                            $property->setName( $properties[4][$i] );
                            if ( $properties[1][$i] ) {
                                $property->setVisibility( $properties[1][$i] );
                            } else {
                                unset( $property->visibility );
                            }
                            if ( $properties[2][$i] ) {
                                $property->setStatic( true );
                            } else {
                                unset( $property->static );
                            }
                            if ( $properties[5][$i] ) {
                                $property->setValue( $properties[5][$i] );
                            } else {
                                unset( $property->value );
                            }
                            // TODO: Add support for documentation comments
                            unset( $property->doc_comment );
                            $response_array[$ic] = $property;
                            $flag = false;
                        }
                    }
                }
                // check multiple response
                if ( count( $response_array ) > 1 ) {
                    $response = array_merge( $response, $response_array );
                } else {
                    $response[] = AspectOUtils::FindUniqueArray( $response_array );
                }
                $response_array = null;
            } else {
                $property = new Property();
                $property->setClass( $properties[3][$i] );
                $property->setName( $properties[4][$i] );
                if ( $properties[1][$i] ) {
                    $property->setVisibility( $properties[1][$i] );
                } else {
                    unset( $property->visibility );
                }
                if ( $properties[2][$i] ) {
                    $property->setStatic( true );
                } else {
                    unset( $property->static );
                }
                if ( $properties[5][$i] ) {
                    $property->setValue( $properties[5][$i] );
                } else {
                    unset( $property->value );
                }
                // TODO: Add support for documentation comments
                unset( $property->doc_comment );
                $response[] = $property;
            }
        }
        // check nulls
        for ( $ir = 0; $ir < count( $response ); $ir++ ) {
            if ( isset( $response[$ir] ) && is_null( $response[$ir] ) ) {
                unset( $response[$ir] );
            }
        }
        return AspectOUtils::FindUniqueArray( $response );
    }
    /**
     * Parses aspect's code and return one or more DeclaredParent's Intertype's objects
     *
     * @param String $code
     * @return DeclaredParent
     */
    protected function ParseIntertypesInheritance ( $code ) {
        $response = $response_array = ''; $flag = false;
        preg_match_all( '/\s*declare[_|\s]parent[s]?\s*:\s*([\w*]*)\s*(extends|implements)\s*(\w*);/',
                        $code, $inheritances );
        for ( $i = 0; $i < count( $inheritances[0] ); $i++ ) {
            if ( $inheritances[1][$i] ) {
                $child_class[$i] = str_replace( ' ', '', $inheritances[1][$i] );
                if ( preg_match( '/([\w*]*)([^\w]+)(?(2)([\w*]*))/', $child_class[$i] ) ) {
                    $classes = glob( $this->config->getClassesPath() . '*.php' );
                    for ( $ic = 0; $ic < count( $classes ); $ic++ ) {
                        $inheritance = new Inheritance();
                        preg_match( '/\/?(\w*)\.php/', $classes[$ic], $class_name );
                        if ( class_exists( $class_name[1] ) ) {
                            // wildcard is *
                            if ( $child_class[$i] == '*' ) {
                                $flag = true;
                            // wildcard contains * (*ex*amp*le*)
                            } elseif ( preg_match('/('.str_replace('*','.*',$child_class[$i])
                                       .')/',$class_name[1]) ) {
                                $flag = true;
                            }
                            if ( $flag ) {
                                $inheritance->setClass( $class_name[1] );
                                $inheritance->setAction( $inheritances[2][$i] );
                                $inheritance->setParent( $inheritances[3][$i] );
                                $response_array[$ic] = $inheritance;
                                $flag = false;
                            }
                        }
                    }
                    // check multiple response
                    if ( count( $response_array ) > 1 ) {
                        $response = array_merge( $response, $response_array );
                    } else {
                        $response[] = AspectOUtils::FindUniqueArray( $response_array );
                    }
                    $response_array = null;
                }
                if ( !preg_match( '/([\w*]*)([^\w]+)(?(2)([\w*]*))/', $child_class[$i] ) ) {
                    $inheritance = new Inheritance();
                    $inheritance->setClass( str_replace( ' ', '', $inheritances[1][$i] ) );
                    $inheritance->setAction( $inheritances[2][$i] );
                    $inheritance->setParent( $inheritances[3][$i] );
                    $response[] = $inheritance;
                }
            }
        }
        // check nulls
        for ( $ir = 0; $ir < count( $response ); $ir++ ) {
            if ( isset( $response[$ir] ) && is_null( $response[$ir] ) ) {
                unset( $response[$ir] );
            }
        }
        return AspectOUtils::FindUniqueArray( $response );
    }
    /**
     * Parses aspect's pointcuts and returns one or mote Pointcut's objects
     *
     * @param String $code
     * @return Pointcut
     */
    protected function ParsePointCuts ( $code ) {
        $response = '';
        preg_match_all( '/pointcut\s*(\w*)\s*\:\s*(call|execution|new|get|set)\s*\((.*)\)\;/',
                        $code, $pointcuts );
        for ( $i = 0; $i < count( $pointcuts[0] ); $i++ ) {
            $pointcut = new PointCut();
            $pointcut->setName( $pointcuts[1][$i] );
            $pointcut->setType( $pointcuts[2][$i] );
            $pointcut->setJoinpoint( self::ParseJoinPoints( $pointcut->getType(),
                                     $pointcuts[3][$i] ) );
            $response[$i] = $pointcut;
        }
        return $response;
    }
    /**
     * Parses pointcut's content and returns one or more Joinpoint's objects
     *
     * @param String $pointcut_type
     * @param String $code
     * @return Joinpoint
     */
    protected function ParseJoinPoints ( $pointcut_type, $code ) {
        switch ( $pointcut_type ) {
            case 'execution' :
                preg_match( '/([public|private|protected|*]{1,9})\s*([\w*]*)\s*([\w*]*)\s*\(([\d*]*)\)/',
                            $code, $exec );
                $joinpoint_array = array(
                                         'class'      => $exec[2],
                                         'name'       => $exec[3],
                                         'visibility' => $exec[1],
                                         'arguments'  => $exec[4]
                                         );
                $joinpoint = $this->ParseClass( $joinpoint_array );
                break;
            case 'call' :
                preg_match( '/([\w*]*)\s*([\w*]*)\s*\(([\d*]*)\)/', $code, $call );
                $joinpoint_array = array(
                                         'class'     => $call[1],
                                         'name'      => $call[2],
                                         'arguments' => $call[3]
                                         );
                $joinpoint = $this->ParseClass( $joinpoint_array );
                break;
            case 'new' :
                preg_match( '/([\w*]*)\s*\(([\d*]*)\)/', $code, $new );
                $joinpoint_array = array(
                                         'class'     => $new[1],
                                         'arguments' => $new[2]
                                         );
                $joinpoint = $this->ParseClass( $joinpoint_array );
                break;
            case 'get' :
                preg_match( '//', $code, $get );
                $joinpoint_array = array(
                                         'class' => $get,
                                         ''
                                         );
                break;
            case 'set' :
                break;
        }
        return $joinpoint;
    }
    /**
     * Parses aspect's joinpoint's class's data
     *
     * @param Array $joinpoint_array
     * @return Joinpoint
     */
    protected function ParseClass ( Array $joinpoint_array ) {
        $response = '';
        $flag = false;
        try {
            // look for class wildcard
            if ( preg_match( '/([\w*]*)([^\w]+)(?(2)([\w*]*))/', $joinpoint_array['class'] ) ) {
                $classes = glob( $this->config->getClassesPath() . '*.php' );
                for ( $i = 0; $i < count( $classes ); $i++ ) {
                    $joinpoint = new JoinPoint();
                    preg_match( '/\/?(\w*)\.php/', $classes[$i], $class_name );
                    // class wildcard is *
                    if ( $joinpoint_array['class'] == '*' ) {
                        $flag = true;
                    // class wildcard contains * (e*xam*pl*e)
                    } elseif ( preg_match('/(.*'.str_replace('*','.*',$joinpoint_array['class'])
                               . '.*)/', $class_name[1]) ) {
                        $flag = true;
                    }
                    if ( $flag ) {
                        $joinpoint->setClass( $class_name[1] );
                        $joinpoint->setMethod( $this->ParseMethod( $joinpoint_array,$class_name[1] ) );
                        $response[$i] = $joinpoint;
                        $flag = false;
                    }
                }
                $response = AspectOUtils::FindUniqueArray( $response );
            // normal joinpoint set (no wilcard)
            } else {
                $joinpoint = new JoinPoint();
                $joinpoint->setClass( $joinpoint_array['class'] );
                $joinpoint->setMethod( $this->ParseMethod( $joinpoint_array, $joinpoint_array['class'] ) );
                $response = $joinpoint;
            }
        } catch ( ReflectionException $e ) {
            throw new AspectOException( $e->getMessage() );
        }
        return $response;
    }
    /**
     * Parses aspect's joinpoint's class's method's data
     *
     * @param Array $method_array
     * @return Method
     */
    protected function ParseMethod ( Array $method_array, $class_name ) {
        $response = '';
        $flag = false;
        try {
            // method is a constructor
            if ( !isset( $method_array['name'] ) || is_null( $method_array['name'] ) ) {
                $ref_class_constructor = new ReflectionClass( $class_name );
                if ( $ref_class_constructor->getConstructor() ) {
                    $method_array['name'] = $ref_class_constructor->getConstructor()->getName();
                } else {
                    throw new AspectOException( $class_name.' has no constructor. '
                                                .'Set manually the '.$class_name.' constructor.' );
                }
            }
            // look for method wilcard
            if ( preg_match( '/([\w*]*)([^\w]+)(?(2)([\w*]*))/', $method_array['name'],
                             $method_match ) ) {
                $ref_class = new ReflectionClass( $class_name );
                foreach ( $ref_class->getMethods() as $ref_method ) {
                    $method = new Method();
                    // method wildcard is *
                    if ( $method_array['name'] == '*' ) {
                        // sets or unsets visibility
                        if ( isset( $method_array['visibility'] ) ) {
                            if ( $method_array['visibility'] == '*' ) {
                                $method->setVisibility( AspectOUtils::getVisibility( $ref_class->getMethod( $ref_method->getName() ) ) );
                            } else {
                                $method->setVisibility( $method_array['visibility'] );
                            }
                        } else {
                            unset( $method->visibility );
                        }
                        $flag = true;
                    // method wildcard contains *
                    } elseif ( preg_match('/.*('.str_replace('*','.*',$method_array['name']).').*/',
                                          $ref_method->getName()) ) {
                        // sets or unsets visibility
                        if ( isset( $method_array['visibility'] ) ) {
                            $method->setVisibility( $method_array['visibility'] );
                        } else {
                            unset( $method->visibility );
                        }
                        $flag = true;
                    }
                    if ( $flag ) {
                        $method->setName( $ref_method->getName() );
                        if ( $ref_method->isFinal() ) {
                            $method->setFinal( true );
                        } else {
                            unset( $method->final );
                        }
                        if ( $ref_method->isStatic() ) {
                            $method->setStatic( true );
                        } else {
                            unset( $method->static );
                        }
                        $method->setArguments( $this->ParseArguments( $ref_method,
                                                                      $method_array['arguments'] ) );
                        if ( $parsed_code = $this->ParseMethodCode( $ref_method ) ) {
                            $method->setCode( $parsed_code );
                            unset( $parsed_code );
                        } else {
                            unset( $method->code );
                        }
                        unset( $method->class );
                        $response[] = $method;
                        $flag = false;
                    }
                }
            // normal method set (no wildcard)
            } else {
                $method = new Method();
                $method->setName( $method_array['name'] );
                if ( isset( $method_array['visibility'] ) ) {
                    $method->setVisibility( $method_array['visibility'] );
                } else {
                    unset( $method->visibility );
                }
                $ref_method = new ReflectionMethod( $class_name, $method_array['name'] );
                if ( $ref_method->isFinal() ) {
                    $method->setFinal( true );
                } else {
                    unset( $method->final );
                }
                if ( $ref_method->isStatic() ) {
                    $method->setStatic( true );
                } else {
                    unset( $method->static );
                }
                $method->setArguments( $this->ParseArguments( $ref_method, $method_array['arguments'] ) );
                if ( $parsed_code = $this->ParseMethodCode( $ref_method ) ) {
                    $method->setCode( $parsed_code );
                    unset( $parsed_code );
                } else {
                    unset( $method->code );
                }
                unset( $method->class );
                $response = $method;
            }
        } catch ( ReflectionException $e ) {
            throw new AspectOException( $e->getMessage() );
        }
        // method array is unique
        return AspectOUtils::FindUniqueArray( $response );
    }
    /**
     * Parses aspect's method's arguments and return the current argument's number
     *
     * @param ReflectionMethod $method
     * @param String $arguments
     * @return Integer
     */
    protected function ParseArguments ( ReflectionMethod $method, $arguments ) {
        if ( $arguments == '*' ) {
            return count( $method->getParameters() );
        } else {
            return (int)$arguments;
        }
    }
    /**
     * Parses aspect's method's code and returns that code
     *
     * @param ReflectionMethod $method
     * @return String
     */
    protected function ParseMethodCode ( ReflectionMethod $method ) {
        $regex = '/(?:final|\s*)\s*(?:public|private|protected|\s*)\s*(?:static|\s*)\s*function\s*'
               . $method->getName() . '\s*\(.*\)\s*\{(.*)\}'
               . '(?=\s*(?:final|public|private|protected|function)|\s*\}?\s*\?\>|\s*\}\Z)/sUx';
        if ( preg_match( $regex,
                         AspectOUtils::RemoveComments( file_get_contents( $method->getFileName() ) ),
                         $method_code ) ) {
            return $method_code[1];
        } else {
            return false;
        }
    }
    /**
     * Recursive method to set names of the classes to weave
     *
     * @param Mixed $variable
     */
    public function FindClasses ( $variable ) {
        $object_values = is_object( $variable) ? get_object_vars( $variable ) : null;
        if ( is_null( $object_values ) && is_array( $variable ) ) {
            $this->FindClasses( $variable );
        } elseif ( $object_values && count( $object_values ) > 1 ) {
            foreach ( $object_values as $key => $value ) {
                if ( is_array( $value ) ) {
                    for ( $i = 0; $i < count( $value ); $i++ ) {
                        $this->FindClasses( $value[$i] );
                    }
                    foreach ( $value as $val ) {
                        $this->FindClasses( $val );
                    }
                } elseif ( is_object( $value ) ) {
                    $this->FindClasses( $value );
                } else {
                    if ( $key == 'class' ) {
                        if ( isset( $this->aspect->classes[$value] ) ) {
                            $this->aspect->classes[$value] = ++$this->aspect->classes[$value];
                        } else {
                            $this->aspect->classes[$value] = 1;
                        }
                    }
                }
            }
        }
    }
}
?>
