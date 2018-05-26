<?php

namespace Khoai\ChangePrice\Controller\Index;

class Config extends \Magento\Framework\App\Action\Action
{

  protected $helperData;

  public function __construct(
      \Magento\Framework\App\Action\Context $context,
      \Khoai\ChangePrice\Helper\Data $helperData

  )
  {
    $this->helperData = $helperData;
    return parent::__construct($context);
  }

  public function execute()
  {

    echo $this->helperData->getGeneralConfig('enable');
    echo $this->helperData->getGeneralConfig('display_text');
    exit();

  }
}