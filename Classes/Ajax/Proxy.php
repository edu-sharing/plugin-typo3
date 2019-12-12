<?php

use Metaventis\Edusharing\Library;

class renderProxy
{

    function getRenderHtml($url)
    {
        $inline = false;
        $curl_handle = curl_init($url);
        curl_setopt($curl_handle, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
        $inline = curl_exec($curl_handle);
        if (curl_errno($curl_handle)) {
            error_log("Error when fetching $url: " . curl_error($curl_handle));
        }
        curl_close($curl_handle);
        return $inline;
    }


    function display($html, $eduObj)
    {
        $html = str_replace(array("\r\n", "\r", "\n"), '', $html);

        /*
        * replaces {{{LMS_INLINE_HELPER_SCRIPT}}}
        */
        $html = str_replace(
            "{{{LMS_INLINE_HELPER_SCRIPT}}}",
            "index.php?eID=edusharing_proxy&edusharing_external=true&data-edusharing_uid=" . $eduObj['uid'],
            $html
        );

        echo $html;
    }

}

function runProxy()
{
    $eduObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
        ->getConnectionForTable('tx_edusharing_object')
        ->select(
            ['*'],
            'tx_edusharing_object',
            [
                'uid' => $_GET['data-edusharing_uid']
            ]
        )
        ->fetch();

    $renderProxy = new renderProxy();

    if (empty($_GET['data-edusharing_uid']) || empty($eduObj['objecturl'])) {
        $renderProxy->display('Fehlerhaftes Objekt', $eduObj);
        exit();
    }

    $eduObj['nodeid'] = substr($eduObj['objecturl'], strrpos($eduObj['objecturl'], '/') + 1);

    $library = new Library;

    if ($_GET['edusharing_external']) {
        $url = $library->getContenturl($eduObj, 'window');
        header('Location: ' . $url);
        exit(0);
    } else if ($_GET['data-edusharing_mediatype'] == 'saved_search') {
        $html = $library->getSavedSearch(
            $eduObj['nodeid'],
            $_GET['data-edusharing_savedsearch_limit'],
            0,
            $_GET['data-edusharing_savedsearch_sortproperty'],
            $_GET['data-edusharing_savedsearch_template']
        );
        $html = json_decode($html) . '<div style="clear:both"></div>';
    } else {
        $url = $library->getContenturl($eduObj, 'inline');
        $html = $renderProxy->getRenderHtml($url);
    }
    $renderProxy->display($html, $eduObj);
}

try {
    runProxy();
} catch (Exception $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
} catch (Error $e) {
    error_log($e->getMessage());
    error_log($e->getTraceAsString());
}
