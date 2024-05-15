<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\GoogleDriveCore\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Url;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const MODULE_NAME = 'Nimasystems_GoogleDriveCore';

    const XML_PATH = 'googledrivecore/google_drive_settings/';

    const STORE_DIR = 'google_drive';

    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var DirectoryList
     */
    protected DirectoryList $directoryList;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ModuleListInterface
     */
    protected ModuleListInterface $moduleList;

    /**
     * @var Url
     */
    protected Url $urlHelper;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param ModuleListInterface $moduleList
     * @param DirectoryList $directoryList
     * @param Url $urlHelper
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        ModuleListInterface   $moduleList,
        DirectoryList         $directoryList,
        Url                   $urlHelper
    )
    {
        $this->context = $context;
        $this->storeManager = $storeManager;
        $this->moduleList = $moduleList;
        $this->directoryList = $directoryList;
        $this->urlHelper = $urlHelper;

        parent::__construct($context);
    }

    /**
     * @param string $code
     * @param integer|null $storeId
     * @param string $path
     * @return mixed
     */
    public function getStoreConfig(string $code, int $storeId = null, string $path = self::XML_PATH)
    {
        return $this->scopeConfig->getValue(
            $path . $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param string $code
     * @param integer|null $storeId
     * @param string $path
     * @return bool
     */
    public function getStoreConfigFlag(string $code, int $storeId = null, string $path = self::XML_PATH): bool
    {
        return $this->scopeConfig->isSetFlag(
            $path . $code, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getGoogleServiceDir(): string
    {
        return $this->directoryList->getPath('var') . DS . Data::STORE_DIR;
    }

    public function getGoogleServiceAccount(): ?string
    {
        $filename = $this->getStoreConfig('service_account_file');

        if (!$filename) {
            return null;
        }

        $filename = $this->getGoogleServiceDir() . DS . $filename;

        if (!file_exists($filename)) {
            return null;
        }

        return file_get_contents($filename);
    }

    /**
     * @param string $code
     * @param integer|null $storeId
     * @param string $path
     * @return false|string[]
     */
    public function getStoreConfigArray(string $code, int $storeId = null, string $path = self::XML_PATH)
    {
        return explode(',', $this->scopeConfig->getValue(
            $path . $code, ScopeInterface::SCOPE_STORE, $storeId
        ));
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->moduleList
                   ->getOne(self::MODULE_NAME)['setup_version'];
    }
}
