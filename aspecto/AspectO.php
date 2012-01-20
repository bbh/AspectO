<?
/**
 * Parses, validates and weaves aspects and classes
 *
 * <code>
 * $aspecto = new AspectO( $AspectOConfig );
 * </code>
 *
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2006 Basilio Brice&ntilde;o Hern&aacute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.7
 * @todo aspect->class/method weaving
 */
class AspectO extends Overload
{
    public function __construct ( AspectOConfig &$config ) {
        if ( $config->getMode() && $config->getMode() == 'debug' ) {
            $this->WeaveAspects( $config, $this->ValidateAspects( $this->ParseAspects( $config ) ) );
        }
    }
    /**
     * Returns parsed Aspect objects
     *
     * @param AspectOConfig $config
     * @return Mixed
     */
    public function ParseAspects ( AspectOConfig $config ) {
        $aspects = glob( $config->getAspectsPath() . '*.php' );
        for ( $i = 0; $i < count( $aspects ); $i++ ) {
            $aspect = new AspectOParser( $aspects[$i], $config );
            $aspects[$i] = $aspect->getAspect();
        }
        if ( is_array( $aspects ) && count( $aspects ) <= 1 ) {
            foreach ( $aspects as $aspect ) {
                $aspects = $aspect;
            }
        }
        return $aspects;
    }

    /**
     * Validates Aspect objects
     *
     * @param Mixed $aspects
     * @return Mixed
     */
    public function ValidateAspects( $aspects ) {
        if ( is_array( $aspects ) ) {
            foreach ( $aspects as $aspect ) {
                $validate = new AspectOValidator( $aspect );
            }
        } else {
            $validate = new AspectOValidator( $aspects );
        }
        return $aspects;
    }

    /**
     * Weaves Aspects
     *
     * @param AspectOConfig $config
     * @param Mixed $aspect
     */
    protected function WeaveAspects( AspectOConfig &$config, $aspects )
    {
        if ( is_array( $aspects ) && count( $aspects ) > 1 ) {
            foreach ( $aspects as $aspect ) {
                self::WeaveAspects( $config, $aspect );
            }
        } else {
            $weaver = new AspectOWeaver( $config, $aspects );
        }
    }
}
?>
