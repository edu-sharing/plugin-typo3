<?php

namespace Metaventis\Edusharing;

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath("edusharing");
require_once($extensionPath . "/Lib/php-auth-plugin/edu-sharing-plugin/edu-sharing-helper.php");
require_once($extensionPath . "/Lib/php-auth-plugin/edu-sharing-plugin/edu-sharing-helper-base.php");
require_once($extensionPath . "/Lib/php-auth-plugin/edu-sharing-plugin/edu-sharing-auth-helper.php");
require_once($extensionPath . "/Lib/php-auth-plugin/edu-sharing-plugin/edu-sharing-node-helper.php");

use TYPO3\CMS\Core\Utility\GeneralUtility;

use EduSharingHelperBase;
use EduSharingAuthHelper;
use EduSharingNodeHelper;
use Usage;

use Metaventis\Edusharing\Settings\Config;
use Metaventis\Edusharing\Ssl;



class EduRestClient
{
    private $authHelper;
    private $nodeHelper;

    public function __construct()
    {
        $config = GeneralUtility::makeInstance(Config::class);
        $ssl = GeneralUtility::makeInstance(Ssl::class);
        $base = new EduSharingHelperBase(
            $config->get(Config::REPO_URL),
            $ssl->getPrivateKey(),
            $config->get(Config::APP_ID),
        );
        $base->setLanguage('de');
        $this->authHelper = new EduSharingAuthHelper($base);
        $this->nodeHelper = new EduSharingNodeHelper($base);
    }

    /**
     * Fetches a fresh ticket for the given user from edu-sharing.
     */
    public function getTicket(string $username): string
    {
        return $this->authHelper->getTicketForUser($username);
    }

    /**
     * Checks the validity of the given ticket by sending a validate request to edu-sharing.
     */
    public function isTicketValid(string $ticket): bool
    // TODO: Check ticket validity for a given user

    {
        try {
            $info = $this->authHelper->getTicketAuthenticationInfo($ticket);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Creates a new usage entry for the given node for the user that is authenticated by the given
     * ticket.
     */
    public function createUsage(
        string $ticket,
        string $containerId,
        string $resourceId,
        string $nodeId,
        string $nodeVersion
    ): Usage
    {
        $usage = $this->nodeHelper->createUsage(
            $ticket,
            $containerId,
            $resourceId,
            $nodeId,
            $nodeVersion,
        );
        // error_log("Created usage for node " . $nodeId . ": " . $usage->usageId);
        return $usage;
    }

    public function deleteUsage(
        string $nodeId,
        string $usageId
    ): void
    {
        $this->nodeHelper->deleteUsage($nodeId, $usageId);
        // error_log("Deleted usage for node " . $nodeId . ": " . $usageId);
    }

    public function getUsageId(
        $ticket,
        $nodeId,
        $containerId,
        $resourceId
    ): string
    {
        $usageId = $this->nodeHelper->getUsageIdByParameters(
            $ticket,
            $nodeId,
            $containerId,
            $resourceId
        );
        if ($usageId === null) {
            throw new \Exception('Failed find matching usage for node ' . $nodeId);
        }
        return $usageId;
    }

    public function getNodeByUsage(
        $nodeId,
        $version,
        $containerId,
        $resourceId,
        $usageId
    )
    {
        $node = $this->nodeHelper->getNodeByUsage(
            new Usage($nodeId, $version, $containerId, $resourceId, $usageId),
                \DisplayMode::Inline,
            array(
                "width" => $_GET['edusharing_width'],
                "height" => $_GET['edusharing_height'],
            )
        );
        return $node;
    }
}