<?
/**
 * Provides magic methods to set and get properties from a class
 * 
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2006 Basilio Brice&ntilde;o Hern&aacute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.5
 */
abstract class Overload {
    /**
     * Implements the magic method to access properties by methods like getMyVar()
     */
    public function __call( $name, $params ) {
        try {
            if ( preg_match( '/[s|g]et_?\w*/', $name ) ) {
                if ( substr( $name, 0, 3 ) == 'set' ) {
                    $name = preg_replace( '/set_?/', '', $name );
                    if ( property_exists( $this, $name ) ) {
                        $this->{$name} = array_pop( $params );
                        return true;
                    } elseif ( property_exists( $this, $this->ChangePropertyNameToLower( $name ) ) ) {
                        $this->{$this->ChangePropertyNameToLower( $name )} = array_pop( $params );
                        return true;
                    } else { // call to class error handler
                        return false;
                    }
                } elseif ( substr( $name, 0, 3 ) == 'get' ) {
                    $name = preg_replace( '/get_?/', '', $name );
                    if ( property_exists( $this, $name ) ) {
                        return $this->{$name};
                    } elseif ( property_exists( $this, $this->ChangePropertyNameToLower( $name ) ) ) {
                        return $this->{$this->ChangePropertyNameToLower( $name )};
                    } else { // call to class error handler
                        return false;
                    }
    
                } else {
                    return false;
                }
            } else {
                throw new AspectOException( "method $name does not exist\n" );
            }
            return false;
        } catch ( AspectOException $e ) {
            echo $e->__toString(); exit;
        }
    }
    /**
     * Implements the magic method __get to access properties like $this->my_var or $this->MyVar
     *
     * @param String $name
     * @return Mixed
     */
    public function __get( $name ) {
        try {
            if ( property_exists( $this, $name ) ) {
                return $this->{$name};
            } elseif ( property_exists( $this, $this->ChangePropertyNameToLower( $name ) ) ) {
                return $this->{$this->ChangePropertyNameToLower( $name )};
            } else {
                throw new AspectOException( "Property $name does not exist" );
            }
        } catch ( AspectOException $e ) {
            echo $e->__toString(); exit;
        }
    }
    /**
     * Implements the magic method __set to set property's values like $this->myvar or $this->MyVar
     *
     * @param String $name
     * @param Mixed $value
     */
    public function __set( $name, $value ) {
        try {
            if ( property_exists( $this, $name ) ) {
                $this->{$name} = $value;
            } elseif ( property_exists( $this, $this->ChangePropertyNameToLower( $name ) ) ) {
                $this->{$this->ChangePropertyNameToLower( $name )} = $value;
            } else {
                throw new AspectOException( "Property $name does not exist" );
            }
        } catch ( AspectOException $e ) {
            echo $e->__toString(); exit;
        }
    }
    /**
     * Changes a string like my_new_var to MyNewVar
     *
     * @param String $name
     * @return String
     */
    public function ChangePropertyNameToLower ( $name ) {
        return (string) preg_replace( '/^_/', '$1', preg_replace( '/([A-Z]+)/e',
                                                                  "'_'.strtolower('$1')", $name ) );
    }
    /**
     * Destruct any property in the class
     */
    public function __destruct() {
        foreach ( get_object_vars( $this ) as $var ) {
            if ( isset( $var ) ) {
                unset( $var );
            }
        }
    }
}
?>