<?php
namespace Order\PlaceManually\Model\ResourceModel\MyCustomGrid;
 
/* use required classes */
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
 
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
   
    protected $_idFieldName = 'id';

    protected $_logger;
    protected $storeManager;
 
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        AdapterInterface $connection = null,
        AbstractDb $resource = null
    ) {
        $this->_logger = $logger;
        $this->_init('Order\PlaceManually\Model\MyCustomGrid', 'Order\PlaceManually\Model\ResourceModel\MyCustomGrid');
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->storeManager = $storeManager;
    }
    
    protected function _initSelect()
    {
        //my filters
        $this->addFilterToMap('statuslabel', 'statustable.label');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        parent::_initSelect();

                $query = $this->getSelect()->reset(\Zend_Db_Select::COLUMNS)
                //my main table fields
                ->columns('main_table.entity_id')
                ->columns('main_table.increment_id')
                ->columns('main_table.shipping_method')
                ->columns('main_table.base_grand_total')
                ->columns("CONCAT (main_table.customer_firstname, ' ' , main_table.customer_lastname) AS customer_firstname")
                ->columns('main_table.customer_lastname')
                ->columns('main_table.status')    
                ->columns('main_table.created_at')  
                //join main table with other tables
                ->join(
                    ['statustable'=>$this->getTable('sales_order_status')],
                    'main_table.status = statustable.status',
                    [
                        'statuslabel'=>'statustable.label'
                    ]
                )->distinct(true)
                ->where('main_table.manually_order = ?', 1);

                $this->_logger->error("Query: " . $query->__toString());
                return $this;

    }
    
}