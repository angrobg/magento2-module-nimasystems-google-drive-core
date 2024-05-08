<?php
/**
 * Copyright © Nimasystems (info@nimasystems.com). All rights reserved.
 * Please visit Nimasystems.com for license details
 */

declare(strict_types=1);

namespace Nimasystems\GoogleDriveCore\Helper;

use Exception;
use Google\Client;
use Google\Service\Drive\DriveFile;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Throwable;

class Drive extends AbstractHelper
{
    protected Data $dataHelper;
    private ?Client $client = null;

    public function __construct(
        Context $context,
        Data    $dataHelper
    )
    {
        $this->dataHelper = $dataHelper;

        parent::__construct($context);
    }

    public function getClientInstance(): Client
    {
        if (!$this->client) {
            $this->client = $this->getClient();
        }

        return $this->client;
    }

    public function getClient(): Client
    {
        $serviceAccount = $this->dataHelper->getStoreConfig('service_account');

        if (!$serviceAccount) {
            throw new Exception('Service account not configured');
        }

        $client = new Client();
        $client->setAuthConfig(json_decode($serviceAccount, true));
//        $client->useApplicationDefaultCredentials();
        $client->addScope(\Google\Service\Drive::DRIVE);
        return $client;
    }

    protected function getFolderNames(Client $client = null): array
    {
        $folders = $this->getFolders($client);
        $folderNames = [];
        foreach ($folders as $folder) {
            try {
                if (!empty($folder['parent']) && !empty($folders[$folder['parent'][0]]['name'])) {
                    $folderNames[$folder['id']] = $this->getParentName($folders, $folder) . ' >> ' . $folder['name'];
                } else {
                    $folderNames[$folder['id']] = $folder['name'];
                }
            } catch (Throwable $throwable) {
                continue;
            }
        }

        natcasesort($folderNames);

        return $folderNames;
    }

    protected function getParentName($folders, $folder, Client $client = null)
    {

        if (empty($folder['parent']) || empty($folders[$folder['parent'][0]])) {
            return null;
        }

        $parentName = $this->getParentName($folders, $folders[$folder['parent'][0]], $client);

        if ($parentName !== null) {
            return $parentName . ' >> ' . $folders[$folder['parent'][0]]['name'];
        }

        return $folders[$folder['parent'][0]]['name'];
    }

    public function getFolders(Client $client = null, $nextPageToken = null): array
    {
        $client = $client ?: $this->getClientInstance();
        $service = new Google_Service_Drive($client);

        $parameters['q'] = "mimeType='application/vnd.google-apps.folder' and trashed=false";
        $parameters['orderBy'] = 'name';
        $parameters['fields'] = 'files(id,name,parents,shared), nextPageToken';

        if (!empty($nextPageToken)) {
            $parameters['pageToken'] = $nextPageToken;
        }

        $parameters['supportsAllDrives'] = true;
        $parameters['includeItemsFromAllDrives'] = true;

        $folderNames = [];
        $files = $service->files->listFiles($parameters);
        $nextPageToken = $files->getNextPageToken();

        /** @var Google_Service_Drive_DriveFile $file */
        foreach ($files as $file) {
            $name = $file->getName();
            if ($file->getShared()) {
                $name = $name . " (shared)";
            }
            $folderNames[$file->getId()] = [
                'id' => $file->getId(),
                'name' => $name,
                'parent' => $file->getParents(),
            ];
        }

        if (empty($nextPageToken)) {
            return $folderNames;
        }

        return array_merge($folderNames, $this->getFolders($client, $nextPageToken));
    }

    public function getFolderContents(string $folderId, bool $recursive = false, Google_Service_Drive $driveService = null): array
    {
        $driveService = new Google_Service_Drive($this->getClientInstance());
        $optParams = [
            'fields' => 'nextPageToken, files(*)',
            'q' => "'$folderId' in parents and trashed=false",
        ];
        $filesResult = [];
        $results = $driveService->files->listFiles($optParams);
        $nextPageToken = $results->getNextPageToken();

        if (count($results->getFiles()) !== 0) {
            $filesResult = $this->_getFolderContents($driveService, $folderId, $results->getFiles(), $recursive, $filesResult, $nextPageToken);
        }

        return $filesResult;
    }

    private function _getFolderContents(Google_Service_Drive $driveService, $folderId, $files, bool $recursive, array &$filesResult, ?string $nextPageToken = null): array
    {
        /** @var DriveFile $file */
        foreach ($files as $file) {
            $fileId = $file->getId();
            $fileName = $file->getName();
            $fileSize = $file->getSize();
            $fileType = $file->getMimeType();
            $fileParents = $file->getParents();
            $return_file = [
                'id' => $fileId,
                'size' => $fileSize,
                'name' => $fileName,
                'type' => $fileType,
                'parents' => $fileParents,
            ];
            $isFolder = $fileType == 'application/vnd.google-apps.folder';

            if (!$isFolder) {
                $filesResult[$fileId] = $return_file;
            }

            if ($recursive && $isFolder) {
                $optParams = [
                    'q' => "'$fileId' in parents and trashed=false",
                    'fields' => 'nextPageToken, files(*)',
                ];

                $results = $driveService->files->listFiles($optParams);
//                $nextPageToken = $results->getNextPageToken();

                if (count($results->getFiles()) !== 0) {
                    $files_sub = $results->getFiles();
                    $files_sub_arr = $this->_getFolderContents($driveService, $fileId, $files_sub, $recursive, $filesResult);

                    foreach ($files_sub_arr as $file_sub) {
                        $file_sub_id = $file_sub['file_id'];
                        $filesResult[$file_sub_id] = $file_sub;
                    }
                }
            }
        }

        // next page
        if ($nextPageToken) {
            $optParams = [
                'q' => "'$folderId' in parents and trashed=false",
                'fields' => 'nextPageToken, files(*)',
                'pageToken' => $nextPageToken,
            ];

            $results = $driveService->files->listFiles($optParams);
            $nextPageToken = $results->getNextPageToken();
            $filesResult = $this->_getFolderContents($driveService, $folderId, $results, $recursive, $filesResult, $nextPageToken);
        }


        return $filesResult;
    }
}
