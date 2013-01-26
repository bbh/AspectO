<?php
/**
 * Extends Exception and provides a customized message
 *
 * @author Basilio Briceño H. <bbh@tampico.org.mx>
 * @copyright Copyright &copy; 2006 Basilio Briceño Hernández.
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version 0.1.3
 */
class AspectOException extends Exception
{
    const FILE_NOT_EXISTENT = 'File not existent';
    public function __construct( $message, $code = 0 )
    {
        parent::__construct( $message, $code );
    }
    
    public function __toString()
    {
        $response  = '<p><b>' . __CLASS__ . "</b> [{$this->code}]<br/>\n";
        $response .= "[message: {$this->message}]<br/>\n";
        $response .= "[file: {$this->file}][line:{$this->line}]\n</p>";
        return $response;
    }
}
?>