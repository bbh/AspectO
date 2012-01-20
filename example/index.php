<?
function __autoload ( $class )
{   $paths = array( 'classes/', '../aspecto/' );
    foreach ( $paths as $path )
    {   $class_path = $path . $class . '.php';
        if ( file_exists( $class_path ) ) { require $class_path; }
    }
}
$aspecto = new AspectO( new AspectOConfig( true, 'aspects/', 'classes/', 'weaved_classes/' ) );
?>