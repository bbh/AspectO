<?php
class Actions extends Account implements InterfaceTest
{
/**
     * Foo var
     *
     * @var sometype
     */

public function __construct (  )
{

        $this->logger = new MyLogClass();
    
        echo 'Ready to work!';
    
}
/**
     * This is my transfer function
     *
     * @param Account $fromAccount
     * @param Account $toAccount
     * @param int $amount
     */
private function transfer ( $fromAccount, $toAccount, $amount )
{

        if ( $fromAccount->getBalance() < amount ) {
            throw new Exception();
        }

        $fromAccount->withdraw( $amount );

        $toAccount->deposit( $amount );
    
}

protected function someMethod (  )
{

        echo 'arounding, before';
        
$this->someMethod_AROUND();

        echo 'arounding, after';
    
}
private static function newMethod ( $newVar,$newvarDos )
{

		echo 'someAction in new method';
	
}

protected function someMethod_AROUND (  )
{

        echo 'arounding, before';
        
        echo 'this is what I do';
    
        echo 'arounding, after';
    
}

}