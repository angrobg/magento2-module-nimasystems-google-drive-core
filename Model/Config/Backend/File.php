<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\GoogleDriveCore\Model\Config\Backend;

use Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Nimasystems\GoogleDriveCore\Helper\Data;

class File extends \Magento\Config\Model\Config\Backend\File
{
    protected Data $googleDriveHelper;

    /**
     * @param DirectoryList $directoryList
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param UploaderFactory $uploaderFactory
     * @param RequestDataInterface $requestData
     * @param Filesystem $filesystem
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(Data                 $googleDriveHelper,
                                Context              $context,
                                Registry             $registry,
                                ScopeConfigInterface $config,
                                TypeListInterface    $cacheTypeList,
                                UploaderFactory      $uploaderFactory,
                                RequestDataInterface $requestData,
                                Filesystem           $filesystem,
                                AbstractResource     $resource = null,
                                AbstractDb           $resourceCollection = null,
                                array                $data = [])
    {
        $this->googleDriveHelper = $googleDriveHelper;

        parent::__construct($context, $registry, $config, $cacheTypeList, $uploaderFactory,
            $requestData, $filesystem, $resource, $resourceCollection, $data);
    }

    /**
     * @return string[]
     */
    public function _getAllowedExtensions(): array
    {
        return ['json'];
    }

    protected function _getUploadDir(): string
    {
        return $this->getDir();
    }

    protected function getDir(): string
    {
        return $this->googleDriveHelper->getGoogleServiceDir();
    }
}
