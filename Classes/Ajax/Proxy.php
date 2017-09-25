<?php

use metaVentis\edusharing;

//secure

class renderProxy {

    public function getContenturl($eduObj, $displayMode = 'inline') {
        $contenturl = edusharing\Appconfig::$repo_url . '/renderingproxy';
        $contenturl .= '?app_id=' . urlencode ( edusharing\Appconfig::$app_id );
       // $sessionId = $eduobj->SID;
        //$contenturl .= '&session=' . urlencode ( $sessionId );
        $contenturl .= '&rep_id=' . edusharing\Appconfig::$repo_id;
        $contenturl .= '&obj_id=' . $eduObj['nodeid'];
        $contenturl .= '&resource_id=' . urlencode ( $eduObj['uid'] );
        $contenturl .= '&course_id=' . urlencode ( $eduObj['contentid']);
        $contenturl .= '&display=' . $displayMode;
        if($displayMode === 'window')
            $contenturl .= '&closeOnBack=true';
        $contenturl .= '&width=' . $_GET['edusharing_width'];
        $contenturl .= '&height='  . $_GET['edusharing_height'];
        $contenturl .= '&language=' . 'de';
        $contenturl .= '&version=' . $eduObj['version'];
        $contenturl .= $this -> getSecurityParams();

        return $contenturl;
    }

    function getRenderHtml($url) {
        $inline = "";
        try {
            $curl_handle = curl_init($url);
            curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl_handle, CURLOPT_HEADER, 0);
            curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_handle, CURLOPT_USERAGENT, $_SERVER ['HTTP_USER_AGENT']);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, false);
            $inline = curl_exec($curl_handle);
            curl_close($curl_handle);
        } catch ( Exception $e ) {
            error_log ( print_r ( $e, true ) );
            curl_close ( $curl_handle );
            return false;
        }
        return $inline;
    }


    function display($html, $eduObj) {
        $html = str_replace ( array (
            "\r\n",
            "\r",
            "\n"
        ), '', $html );

        /*
        * replaces {{{LMS_INLINE_HELPER_SCRIPT}}}
        */
        $html = str_replace(
            "{{{LMS_INLINE_HELPER_SCRIPT}}}",
            "index.php?eID=edusharing_proxy&edusharing_external=true&edusharing_uid=".$eduObj['uid'],
            $html);

        /*
         * replaces <es:title ...>...</es:title>
         */
        //$html = preg_replace ( "/<es:title[^>]*>.*<\/es:title>/Uims", $eduObj['printTitle'], $html );
        /*
         * For images, audio and video show a capture underneath object
         */
        $mimetypes = array (
            'image',
            'video',
            'audio'
        );
        foreach ( $mimetypes as $mimetype ) {
            if (strpos ( $eduObj['mimetype'], $mimetype ) !== false)
                $html .= '<p class="caption">' . $eduObj['printTitle'] . '</p>';
        }

        echo $html;
    }

    public function getSecurityParams() {
        $paramString = '';
        $ts = round ( microtime ( true ) * 1000 );
        $paramString .= '&ts=' . $ts;
        $paramString .= '&u=' . urlencode( 'sp4DWsQVmJg=' ); //es_guest

        $signature = '';
        $priv_key = edusharing\Appconfig::$app_private_key;
        $pkeyid = openssl_get_privatekey ( $priv_key );
        openssl_sign ( edusharing\Appconfig::$app_id . $ts, $signature, $pkeyid );
        $signature = base64_encode ( $signature );
        openssl_free_key ( $pkeyid );
        $paramString .= '&sig=' . urlencode ( $signature );
        $paramString .= '&signed=' . urlencode(edusharing\Appconfig::$app_id.$ts);

        return $paramString;
    }
}






$eduObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
    ->getConnectionForTable('tx_edusharing_object')
    ->select(
        ['*'],
        'tx_edusharing_object',
        [
            'uid' => $_GET['edusharing_uid']
        ]
    )
    ->fetch();

$renderProxy = new renderProxy ();

if(empty($_GET['edusharing_uid']) || empty($eduObj['nodeid'])) {
    $renderProxy -> display ( 'Fehlerhaftes Objekt', $eduObj );
    exit();
}

$eduObj['nodeid'] = substr($eduObj['nodeid'], strrpos($eduObj['nodeid'], '/') + 1);

if($_GET['edusharing_external']) {
    $url = $renderProxy -> getContenturl($eduObj, 'window');
    header('Location: ' . $url);
    exit();
}

$url = $renderProxy -> getContenturl($eduObj);
$html = $renderProxy->getRenderHtml($url);
$renderProxy -> display ( $html, $eduObj );