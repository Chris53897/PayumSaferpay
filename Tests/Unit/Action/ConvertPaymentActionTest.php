<?php declare(strict_types=1);

namespace Karser\PayumSaferpay\Tests\Unit\Action;

use Karser\PayumSaferpay\Action\ConvertPaymentAction;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Model\Payment;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Request\Generic;
use Payum\Core\Tests\GenericActionTest;

class ConvertPaymentActionTest extends GenericActionTest
{
    protected $requestClass = Convert::class;
    protected $actionClass = ConvertPaymentAction::class;


    /**
     * @test
     */
    public function shouldImplementActionInterface(): void
    {
        $rc = new \ReflectionClass(ConvertPaymentAction::class);
        $this->assertTrue($rc->implementsInterface(ActionInterface::class));
    }

    /**
     * @test
     */
    public function couldBeConstructedWithoutAnyArguments(): void
    {
        self::assertNotNull(new $this->actionClass());
    }

    public function provideSupportedRequests()
    {
        return array(
            array(new $this->requestClass(new Payment(), 'array')),
            array(new $this->requestClass($this->createMock(PaymentInterface::class), 'array')),
            array(new $this->requestClass(new Payment(), 'array', $this->createMock('Payum\Core\Security\TokenInterface'))),
        );
    }
    public function provideNotSupportedRequests()
    {
        return array(
            array('foo'),
            array(array('foo')),
            array(new \stdClass()),
            array($this->getMockForAbstractClass(Generic::class, array(array()))),
            array(new $this->requestClass(new \stdClass(), 'array')),
            array(new $this->requestClass(new Payment(), 'foobar')),
            array(new $this->requestClass($this->createMock(PaymentInterface::class), 'foobar')),
        );
    }

    /**
     * @test
     */
    public function shouldCorrectlyConvertOrderToDetailsAndSetItBack()
    {
        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        $action = new ConvertPaymentAction();
        $action->execute($convert = new Convert($payment, 'array'));
        $details = $convert->getResult();
        $this->assertNotEmpty($details);
        self::assertSame([
            'Payment' => [
                'Amount' => [
                    'Value' => 123,
                    'CurrencyCode' => 'USD',
                ],
                'OrderId' => 'theNumber',
                'Description' => 'the description',
            ],
            'Transaction' => [
                'Type' => 'PAYMENT',
                'Status' => NULL,
                'Amount' => [
                    'Value' => 123,
                    'CurrencyCode' => 'USD',
                ],
                'OrderId' => 'theNumber',
            ],
        ], $details);
    }

    /**
     * @test
     */
    public function shouldNotOverwriteAlreadySetExtraDetails()
    {
        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('USD');
        $payment->setTotalAmount(123);
        $payment->setDescription('the description');
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        $payment->setDetails(array(
            'foo' => 'fooVal',
        ));
        $action = new ConvertPaymentAction();
        $action->execute($convert = new Convert($payment, 'array'));
        $details = $convert->getResult();
        $this->assertNotEmpty($details);
        self::assertArraySubset([
            'foo' => 'fooVal'
        ], $details);
    }
}
