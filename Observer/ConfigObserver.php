<?php

namespace Khoai\ChangePrice\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface as Logger;

class ConfigObserver implements ObserverInterface
{
    protected $logger;
    protected $indexerFactory;
    protected $indexerCollectionFactory;
    protected $cacheTypeList;
    protected $cacheFrontendPool;

    /**
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger,
        \Khoai\ChangePrice\Helper\Data $helperData,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Action $action,
        \Magento\Indexer\Model\IndexerFactory $indexerFactory,
        \Magento\Indexer\Model\Indexer\CollectionFactory $indexerCollectionFactory,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\Message\ManagerInterface $messageManager

    )
    {
        $this->logger = $logger;
        $this->helperData = $helperData;

        $this->productCollectionFactory = $productCollectionFactory;
        $this->action = $action;

        $this->indexerFactory = $indexerFactory;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->cacheTypeList = $cacheTypeList;

        $this->_messageManager = $messageManager;

    }

    public function execute(EventObserver $observer)
    {

        $newPrice = $this->helperData->getGeneralConfig('new_price');

        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->load();
        //set regular and special price for all store
        foreach ($collection as $productData) {
            $this->action->updateAttributes([$productData->getId()], ['price' => $newPrice], 0);
            //            $this->action->updateAttributes([$productData->getId()], ['special_price' => $newPrice], 0);
        }

        $this->reindexAll();
        $this->flushCache();

    }

    /**
     *  Reindex
     */
    public function reindexAll()
    {
        $indexer = $this->indexerFactory->create();
        $indexerCollection = $this->indexerCollectionFactory->create();
        $ids = $indexerCollection->getAllIds();
        foreach ($ids as $id) {
            $idx = $indexer->load($id);
            if ($idx->getStatus() != 'valid') {
                $idx->reindexRow($id);
            }
        }
    }

    /**
     *  Flush magento caches
     */
    public function flushCache()
    {
        $types = array('config', 'layout', 'block_html', 'collections', 'reflection', 'db_ddl', 'eav', 'config_integration', 'config_integration_api', 'full_page', 'translate', 'config_webservice');
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }
        foreach ($this->cacheFrontendPool as $cacheFrontend) {
            $cacheFrontend->getBackend()->clean();
        }
    }

}
