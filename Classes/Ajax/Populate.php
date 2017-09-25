<?php

?>
<html>
<head>
</head>
<body>
<script type="text/javascript">
    try{
        parent.postMessage(
            {
                'event': 'APPLY_NODE',
                'node':
                    {
                        'object_url': '<?php echo addslashes($_GET['nodeId']) ?>',
                        'title': '<?php echo addslashes($_GET['title']) ?>',
                        'mimetype': '<?php echo addslashes($_GET['mimeType']) ?>',
                        'version': '<?php echo addslashes($_GET['v']) ?>',
                        'height': '<?php echo addslashes($_GET['h']) ?>',
                        'width': '<?php echo addslashes($_GET['w']) ?>'
                    }
                },
            '*');
    } catch (err) {
        alert('ERR APPLY_NODE');
        console.log(err);
    }
</script>
</body>
</html>
