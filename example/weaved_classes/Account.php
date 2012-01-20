<?
class Account extends Actions implements InterfaceTest
{
public $myNewsVar = 'somethings';
public $toEveryClass = 'my value';

public function getAccount (  $someAccount  )
{

$this->getAccount_CALL( $someAccount );

        $this->logger->log( $fromAccount, $toAccount, $amount );
    
}

public function getBalance (  )
{

$this->getBalance_CALL();

        $this->logger->log( $fromAccount, $toAccount, $amount );
    
}

public function withdraw (  $amount  )
{

$this->withdraw_CALL( $amount );

        $this->logger->log( $fromAccount, $toAccount, $amount );
    
}

public function deposit (  $amount  )
{

$this->deposit_CALL( $amount );

        $this->logger->log( $fromAccount, $toAccount, $amount );
    
}

public function getAccount_CALL (  $someAccount  )
{

		return $someAccount;
	
}

public function getBalance_CALL (  )
{

		$someBalance = 500;
		return $someBalance;
	
}

public function withdraw_CALL (  $amount  )
{

	    echo 'withdraw';
		
	
}

public function deposit_CALL (  $amount  )
{

	    echo 'deposit';
		
	
}

}