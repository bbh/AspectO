<?
/**
 * Provides static methods to use across the application.
 *
 * @author Basilio Brice&ntilde;o H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2007 Basilio Brice&ntilde;o Hern&aacute;ndez.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.5
 */
class AspectOUtils
{
    /**
     * Returns visibility from method or property by reflection
     *
     * @param String $subject
     * @return String
     */
    public static function getVisibility ( $subject ) {
        if ( $subject->isPublic() ) { return 'public'; }
        if ( $subject->isPrivate() ) { return 'private'; }
        if ( $subject->isProtected() ) { return 'protected'; }
    }
    /**
     * Finds if array is unique and returns its value
     *
     * @param Mixed $array
     * @return Mixed
     */
    public static function FindUniqueArray ( $array ) {
        if ( is_array( $array ) && count( $array ) <= 1 ) {
            foreach ( $array as $node ) {
                $array = $node;
            }
        }
        return $array;
    }
    /**
     * Removes strings cosidered as code comments
     *
     * @param String $code
     * @return String
     */
    public static function RemoveComments ( $code ) {
        $content = preg_replace( '/(\/\*)(.*)(?(1)\*\/)/sU', '', $code );
        $content = preg_replace( '/\/{2,}(.*)/', '', $content );
        return $content;
    }
}