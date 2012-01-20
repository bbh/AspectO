<?
/**
 * Provides properties to set and get paths of aspects, source and weaved classes
 * 
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2006 Basilio Brice&ntilde;o Hern&acute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.5
 */
class AspectOConfig extends Overload
{
    public $aspects_path;
    public $classes_path;
    public $weaved_classes_path;
    public $mode;
    public function __construct( $mode=false, $aspects=false, $source_classes=false,
                                 $weaved_classes=false ) {
        try {
            if ( $mode ) {
                $this->setMode( true );
                if ( $aspects ) {
                    if ( !file_exists( $aspects ) ) {
                        throw new AspectOException( "Aspects path $aspects provided doesn't exists." );
                    }
                    $this->setAspectsPath( $aspects );
                } else {
                    throw new AspectOException( 'Aspects path not provided.' );
                }
                if ( $source_classes ) {
                    if ( !file_exists( $source_classes ) ) {
                        throw new AspectOException( "Source classes path $source_classes doesn't exists." );
                    }
                    $this->setClassesPath( $source_classes );
                } else {
                    throw new AspectOException( 'Sources classes path not provided.' );
                }
                if ( $weaved_classes ) {
                    if ( !file_exists( $weaved_classes ) ) {
                        throw new AspectOException( "Weaved classes path $weaved_classes doesn't exists." );
                    }
                    $this->setWeavedClassesPath( $weaved_classes );
                } else {
                    throw new AspectOException( 'Weaved classes path not provided.' );
                }
            }
        } catch ( AspectOException $e ) {
            echo $e->__toString(); exit;
        }
    }
}
?>