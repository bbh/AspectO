<?
class Actions
{
    const HOLA = 'MUNDO';
    public $something = 'foo';
    /**
     * Foo var
     *
     * @var sometype
     */
    private $foo;
    public function __construct ()
    {
        echo 'Ready to work!';
    }
    /**
     * This is my transfer function
     *
     * @param Account $fromAccount
     * @param Account $toAccount
     * @param int $amount
     */
    private function transfer ( Account $fromAccount, Account $toAccount, $amount )
    {
        if ( $fromAccount->getBalance() < amount ) {
            throw new Exception();
        }

        $fromAccount->withdraw( $amount );

        $toAccount->deposit( $amount );
    }
    protected function someMethod ()
    {
        echo 'this is what I do';
    }
}
?>