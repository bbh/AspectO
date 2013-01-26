<?php
/**
 * Provides methods to parse, set, get and check the content of a class
 *
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2007 Basilio Brice&ntilde;o Hern&aacute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.35
 * @todo add Constants support
 * @todo check if class is abstract
 */
class AspectOClassParser extends Overload {
  protected $name;
  public $inheritance;
  public $constants;
  public $properties;
  public $methods;
  public function __construct ( $class ) {
    $this->ParseContent( new ReflectionClass( $class ) );
  }
  /**
   * Parses the content of a class in current properties
   *
   * @param ReflectionClass $class
   */
  final protected function ParseContent ( ReflectionClass $class )
  {
    $content = AspectOUtils::RemoveComments( file_get_contents( $class->getFileName() ) );

    $this->setName( $class->getName() );

    // set interfaces
    if ( $class->getInterfaces() ) {

      foreach ( $class->getInterfaces() as $interface ) {

        $inheritance = new Inheritance();
        $inheritance->setParent( $interface->getName() );
        $inheritance->setAction( 'implements' );
        $inheritance->setChild( $class->getName() );
        $inheritances[] = $inheritance;
      }

      $this->inheritance = AspectOUtils::FindUniqueArray( $inheritances );
    }

    // set parent
    if ( $class->getParentClass() ) {

      $inheritance = new Inheritance();
      $inheritance->setParent( $class->getParentClass()->getName() );
      $inheritance->setAction( 'extends' );
      $inheritance->setChild( $class->getName() );

      if ( $this->inheritance ) {

        $this->inheritance[count($this->inheritance)] = $this->inheritance;

      } else {

        $this->inheritance = $inheritance;
      }
    }

    // set properties
    if ( $class->getProperties() ) {

      foreach ( $class->getProperties() as $property ) {

        if ( preg_match( '/(public|private|protected)\s*((?:static)?)\s*\$'.$property->getName()
                 . '\s*=?[\'|\"]?(.*)[\'|\"]?;/', $content, $result ) ) {

          $prop = new Property();

          // name
          $prop->setName( $property->getName() );

          // visibility
          $prop->setVisibility( $result[1] );

          // static
          if ( $result[2] ) {

            $prop->setStatic( true );

          } else {

            unset( $prop->static );
          }

          // value
          if ( $result[3] ) {

            $prop->setValue( $result[3] );

          } else {

            unset( $prop->value );
          }

          // doc comment
          if ( $property->getDocComment() ) {

            $prop->setDocComment( $property->getDocComment() );

          } else {

            unset( $prop->doc_comment );
          }

          unset( $prop->class );

          $properties[] = $prop;
        }
      }

      $this->setProperties( AspectOUtils::FindUniqueArray( $properties ) );
    }

    // set constants
    if ( $class->getConstants() ) {

      $this->setConstants( $class->getConstants() );
    }

    // set methods
    if ( $class->getMethods() ) {

      foreach ( $class->getMethods() as $method ) {

        $meth = new Method();

        $meth->setName( $method->getName() );

        // final
        if ( $method->isFinal() ) {

          $meth->setFinal( 'final' );
        }

        // visibility
        if ( $method->isPublic() ) {

          $meth->setVisibility( 'public' );

        } elseif ( $method->isPrivate() ) {

          $meth->setVisibility( 'private' );

        } elseif ( $method->isProtected() ) {

          $meth->setVisibility( 'protected' );
        }

        if ( $method->isStatic() ) {

          $meth->setStatic( 'static' );
        }

        // parse parameters
        $rx = '/.*\-\sParameters\s\[([\d])\]\s\{.*/';
        if ( preg_match( $rx, $method->__toString(), $result ) ) {

          $num_params = $result[1];

          // iterate parameters
          for ( $i = 0; $i < $num_params; ++$i ) {

            $arg = '';

            // reflect parameter
            $param = new ReflectionParameter( array( $class->getName(),
                                                     $method->getName() ), $i );

            // check is passed by reference
            if ( $param->isPassedByReference() ) {

              $arg = '&';
            }

            $arg .= '$'.$param->getName();

            // parameters has a default value
            if ( $param->isDefaultValueAvailable() ) {

              // if default value is array
              if ( preg_match( '/.*\=\sArray.*/', $param->__toString() ) ) {

                $rx2 = '/.*function\s*'.$method->getName().'\s*\((.*)\)\s*\{.*/';
                if ( preg_match( $rx2, $content, $_arg_all ) ) {

                  $rx3 = '/.*\$'.$param->getName().
                         '\s*\=\s*([a|A]rray\s*\(.*\))\s*\,?\$?.*/';
                  if ( preg_match( $rx3, $_arg_all[1], $_arg_one ) ) {

                    $arg .= ' = ' . $_arg_one[1];
                    unset( $_arg_one );
                  }
                  unset( $_arg_all, $rx3, $rx2 );
                }

              // if default value is simple
              } else {

                $rx2 = '/Parameter\s#0\s\[\s\<optional\>\s\$'.$param->getName().
                       '\s\=\s(\'?.*\'?)\s\]/';
                if ( preg_match( $rx2, $param->__toString(), $_arg_val ) ) {

                  $arg .= ' = ' . $_arg_val[1];
                }
                unset( $rx2 );
              }
            }

            $args[] = $arg;
            unset( $arg );
          }
        }
        unset( $rx );

        // set parameters
        if ( isset ( $args ) && $args ) {
          $meth->setArguments( is_array($args) ? implode(', ',$args) : $args );
          unset( $args );
        }

        // parse lines of code (start,end)
        $rx = '/\@\@\s.*\.php\s([\d]*)\s\-\s([\d]*)/';
        if ( preg_match( $rx, $method->__toString(), $lines ) ) {

          $content_array = preg_split( '/\n/', $content );

          // set code
          $code = ''.PHP_EOL;
          for ( $i = $lines[1]; $i < $lines[2]-1; ++$i ) {

            if ( isset( $content_array[$i] ) ) {
              $code .= $content_array[$i].PHP_EOL;
            }
          }

          $meth->setCode( $code );
        }
        unset( $rx );

        // set docComment
        $meth->setDocComment( $method->getDocComment() );

        unset( $meth->class );

        // set methods array
        $methods[] = $meth;
        unset( $meth );
      }
    }

    $this->setMethods( AspectOUtils::FindUniqueArray( $methods ) );
  }

  /**
   * Adds a Property object to the current properties
   *
   * @param Property $property
   */
  public function addProperty ( Property $property ) {
    if ( isset( $this->properties ) && $this->properties ) {
      if ( is_array( $this->properties ) ) {
        $this->properties[count($this->properties)] = $property;
      } else {
        $temporal_propertiy = $this->properties;
        $this->properties = null;
        $this->properties[0] = $temporal_propertiy;
        $this->addProperty( $property );
      }
    } else {
      $this->setProperties( $property );
    }
  }
  /**
   * Adds a Inheritance object to the current properties
   *
   * @param Inheritance $inheritance
   */
  public function addInheritance ( Inheritance $inheritance ) {
    if ( isset( $this->inheritance ) && $this->inheritance ) {
      if ( is_array( $this->inheritance ) ) {
        $this->inheritance[count($this->inheritance)] = $inheritance;
      } else {
        $temporal_inheritance = $this->inheritance;
        $this->inheritance = null;
        $this->inheritance[0] = $temporal_inheritance;
        $this->addInheritance( $inheritance );
      }
    } else {
      $this->inheritance = $inheritance;
    }
  }
  public function addMethod ( Method $method )
  {
    if ( isset( $this->methods ) && $this->methods ) {

      $this->methods[count($this->methods)] = $method;

    } else {

      $this->setMethods( $method );
    }
  }
  public function MethodExists ( $method_name ) {
    if ( is_array( $this->methods ) ) {
      foreach ( $this->methods as $method ) {
        if ( $method_name == $method->name ) {
          return true;
        }
      }
    } else {
      if ( $method_name = $this->methods ) {
        return true;
      }
    }
    return false;
  }
  /**
   * Sets code into a Method
   *
   * @param String $method_name
   * @param String $code
   */
  public function setMethodCode ( $method_name, $code )
  {
    if ( is_array( $this->methods ) ) {
      for ( $i = 0; $i < count( $this->methods ); $i++ ) {
        if ( $this->methods[$i]->name === $method_name ) {
          $this->methods[$i]->code = $code;
        }
      }
    } else {
      if ( $this->methods->name === $method_name ) {
        $this->methods->code = $code;
      }
    }
  }
  /**
   * Returns the current object into a fixed string
   *
   * @return String
   */
  public function getString () {
    $response = "<?php\nclass $this->name";
    if ( isset( $this->inheritance ) ) {
      $response .= self::getStringInheritanceExtends( $this->inheritance );
      $response .= self::getStringInheritanceImplements( $this->inheritance );
    }
    $response .= "\n{\n";
    $response .= self::getStringProperties( $this->properties );
    $response .= self::getStringMethods( $this->methods );
    $response .= "\n}";
    return $response;
  }
  /**
   * Returns a fixed string extending abstract classes
   *
   * @param Mixed $inheritance
   * @return String
   */
  final protected static function getStringInheritanceExtends ( $inheritance ) {
    $response = '';
    if ( is_array( $inheritance ) ) {
      foreach ( $inheritance as $inh ) {
        $response .= self::getStringInheritanceExtends( $inh );
      }
    } else {
      if ( $inheritance->action === 'extends' ) {
        $response .= ' extends '.$inheritance->parent;
      }
    }
    return $response;
  }
  /**
   * Returns a fixed string implementing some interfase
   *
   * @param Mixed $inheritance
   * @return String
   */
  final protected static function getStringInheritanceImplements ( $inheritance ) {
    $response = '';
    if ( is_array( $inheritance ) ) {
      foreach ( $inheritance as $inh ) {
        $response .= self::getStringInheritanceImplements( $inh );
      }
    } else {
      if ( $inheritance->action === 'implements' ) {
        $response .= ' implements '.$inheritance->parent;
      }
    }
    return $response;
  }
  /**
   * Returns a fixed string declaring constants
   *
   * @param Mixed $constants
   * @return String
   * @todo NOT WORKING YET
   */
  final protected static function getStringConstants ( $constants ) {
    return $constants;
  }
  /**
   * Returns a fixed string declaring properties
   *
   * @param Mixed $properties
   * @return String
   */
  final protected static function getStringProperties ( $properties ) {
    $response = '';
    if ( is_array( $properties ) ) {
      foreach ( $properties as $property ) {
        $response .= self::getStringProperties( $property );
      }
    } else {
      if ( isset( $properties->doc_comment ) && count_chars( $properties->doc_comment ) > 1 ) {
        $response .= $properties->doc_comment . "\n";
      }
      if ( isset( $properties->static ) && $properties->static ) {
        $response .= 'static ';
      }
      if ( isset( $properties->name ) && !$properties->name ) {
        $response .= $properties->visibility . ' $' . $properties->name;
        if ( isset( $properties->value ) &&
           count_chars( $properties->value ) >= 1 && $properties != ' ' ) {
          $response .= ' = '.$properties->value;
        }
        $response .= ";\n";
      }
    }
    return $response;
  }
  /**
   * Returns a fixed string with the content of the methods
   *
   * @param Mixed $methods
   * @return String
   */
  final protected static function getStringMethods ( $methods ) {
    $response = '';
    if ( is_array( $methods ) ) {
      foreach ( $methods as $method ) {
        $response .= self::getStringMethods( $method );
      }
    } else {
      if ( isset( $methods->doc_comment ) && count_chars( $methods->doc_comment ) > 1 ) {
        $response .= $methods->doc_comment . "\n";
      }
      if ( isset( $methods->visibility ) ) {
        $response .= $methods->visibility . ' ';
      }
      if ( isset( $methods->final ) && $methods->final ) {
        $response .= 'final ';
      }
      if ( isset( $methods->static ) && $methods->static ) {
        $response .= 'static ';
      }
      $response .= 'function ' . $methods->name . ' ( ';
      if ( isset( $methods->arguments ) && count_chars( $methods->arguments ) > 1 ) {
        $response .= $methods->arguments;
      }
      $response .= " )\n{\n" . $methods->code . "\n}\n";
    }
    return $response;
  }
}
?>
