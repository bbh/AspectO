<?php
aspect AspectMyLog
{
    public A*ions $myNewVar = 'something';
	
	public Actio*::$myNewVar2 = 'something2';
	
	public *ccount $myNewsVar = 'somethings';
	
	public *::$toEveryClass = 'my value';
	
	private static Actions newMethod ( $newVar, $newvarDos ) {
		echo 'someAction in new method';
	}
	
	declare parent : Actions extends Account;
	
	declare parent : * implements InterfaceTest;
	
	declare parent : Account extends Actions;
	
    pointcut setLogObject : new ( Acti*s (*) );

    pointcut logTransfer : execution ( * Actions transfer (*) );
    
    pointcut callTransfer : call ( Ac*ount * (*) );
    
    pointcut aroundExecPointcut : execution ( protected Actions someMethod (*) );
    
    pointcut arountCallPointcut : call ( Actions someMethod (*) );

    before setLogObject
    {
        $this->logger = new MyLogClass();
    }

    after logTransfer || callTransfer
    {
        $this->logger->log( $fromAccount, $toAccount, $amount );
    }
  
    around aroundExecPointcut
    {
        echo 'arounding, before';
        proceed();
        echo 'arounding, after';
    }
    
    around arountCallPointcut
    {
        echo 'arounding, before';
        proceed();
        echo 'arounding, after';
    }
}
?>
