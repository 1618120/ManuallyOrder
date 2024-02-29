<?php
namespace Order\PlaceManually\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Magento\Framework\App\State;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Model\Service\OrderService;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\Total;

class PlaceOrderCommand extends Command
{
    protected static $defaultName = 'order:place';

    private $state;

    private $customerRepository;

    private $orderService;

    private $orderFactory;

    private $quoteFactory;

    private $orderRepository;

    public $addressRepository;

    private $cartRepository;

    private $cartManagement;

    private $storeManager;

    public function __construct(
        State $state,
        CustomerRepositoryInterface $customerRepository,
        OrderService $orderService,
        QuoteFactory $quoteFactory,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        AddressRepositoryInterface $addressRepository,
        CartRepositoryInterface $cartRepository,
        CartManagementInterface $cartManagement,
        StoreManagerInterface $storeManager
    ) {
        $this->state = $state;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->quoteFactory = $quoteFactory;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->addressRepository = $addressRepository;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->storeManager = $storeManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('order:place')->setDescription('Place an order manually for a customer.')->setDefinition([new InputArgument('customer_id')]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $customerId = $input->getArgument('customer_id');
        $this->state->setAreaCode('adminhtml');

        try {
            $customer = $this->customerRepository->getById($customerId);
            $quote = $this->quoteFactory->create()->loadByCustomer($customer);
            // print_r($quote->getData());die();
            $items = $quote['items_qty'];
            if (!$items) {
                throw new \Exception('The customer does not have any items in their cart.');
            }
            $shippingAddressId = $customer->getDefaultShipping();
            $shippingAddress = $this->addressRepository->getById($shippingAddressId);
            if (!$shippingAddress) {
                throw new \Exception('The customer does not have a default shipping address.');
            }

           $quote->getShippingAddress()
            ->setFirstname($shippingAddress->getFirstname())
            ->setLastname($shippingAddress->getLastname())
            ->setStreet($shippingAddress->getStreet())
            ->setCity($shippingAddress->getCity())
            ->setCountryId($shippingAddress->getCountryId())
            ->setPostcode($shippingAddress->getPostcode())
            ->setRegionId($shippingAddress->getRegionId())
            ->setTelephone($shippingAddress->getTelephone());

            // Collect shipping rates
            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->getShippingAddress()->collectShippingRates();
            $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
            $billingAddressId = $customer->getDefaultBilling();
            $billingAddress = $this->addressRepository->getById($billingAddressId);
            if (!$billingAddress) {
                throw new \Exception('The customer does not have a default billing address.');
            }

            $quote->getBillingAddress()
            ->setFirstname($billingAddress->getFirstname())
            ->setLastname($billingAddress->getLastname())
            ->setStreet($billingAddress->getStreet())
            ->setCity($billingAddress->getCity())
            ->setCountryId($billingAddress->getCountryId())
            ->setPostcode($billingAddress->getPostcode())
            ->setRegionId($billingAddress->getRegionId())
            ->setTelephone($billingAddress->getTelephone());

            $quote->setPaymentMethod('checkmo'); 
            $quote->setInventoryProcessed(false); 
            $quote->save(); 
            $quote->getPayment()->importData(['method' => 'checkmo']); 
            $quote->collectTotals()->save();
            $orderId = $this->cartManagement->placeOrder($quote->getId());

            $order = $this->orderFactory->create()->load($orderId);
            $order->setData('manually_order', 1);
            $order->save();


            $output->writeln("<info>Order placed successfully for customer with ID: $customerId. Order ID: $orderId</info>");

            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } 
        catch (\Exception $e) {
            $output->writeln("<error>Error placing order: {$e->getMessage()}</error>");
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

}
