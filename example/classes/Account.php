<?php
class Account
{
	public function getAccount ( $someAccount )
	{
		return $someAccount;
	}
	public function getBalance()
	{
		$someBalance = 500;
		return $someBalance;
	}
	public function withdraw ( $amount )
	{
	    echo 'withdraw';
		// .. some real code ..
	}
	public function deposit ( $amount )
	{
	    echo 'deposit';
		// .. some real code ..
	}
}
