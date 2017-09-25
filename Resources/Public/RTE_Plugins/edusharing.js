'use strict';

var appconfig;
var ticket;
var node;

CKEDITOR.plugins.add('edusharing', {
    icons: 'edusharing',
    init: function(editor) {

        $.ajax({
            url: TYPO3.settings.ajaxUrls['getAppconfig'],
            method: 'GET',
            success: function(response) {
                appconfig = response;
            }
        });

        $.ajax({
            url: TYPO3.settings.ajaxUrls['getTicket'],
            method: 'GET',
            success: function(response) {
                ticket = response;
            }
        });

        editor.ui.addButton ('edusharing', {
            label: 'edu-sharing',
            tooolbar: 'basicstyles',
            command: 'openModal'
        });

        editor.addCommand('openModal', {
            exec: function(editor){
                require([
                    'TYPO3/CMS/Backend/Modal'
                ], function(Modal) {
                    var configuration = {
                        title: 'edu-sharing',
                        content: getModalContent(),
                        size: Modal.sizes.large,
                        buttons: [
                            {
                                text: 'Einfügen',
                                active: true,
                                trigger: function() {
                                    editor.insertHtml(getInsertHtml());
                                    Modal.dismiss();
                                }
                            }, {
                                text: 'Abbrechen',
                                trigger: function() {
                                    Modal.dismiss();
                                }
                            }
                        ],
                        callback: function() {
                            parent.window.addEventListener("message", function(event) {if(event.data.event=="APPLY_NODE"){
                                node = event.data.node;
                                action();
                            }}, false);
                        }
                    };
                    Modal.advanced(configuration);
                });
            }
        });
    }
});

function action() {
    parent.document.getElementById('edusharing_repository_frame').style.display="none";
    parent.document.getElementById('edusharing_object_dialog').style.display="block";

    if(node.mimetype.indexOf('video') === -1 && node.mimetype.indexOf('image') === -1) {
        parent.document.getElementById('edusharing_fieldset_dimensions').style.display = 'none';
        parent.document.getElementById('edusharing_fieldset_float').style.display = 'none';
    }

    parent.document.getElementById('edusharing_title_display').textContent = node.title;
    parent.document.getElementById('edusharing_height').value = node.height;
    parent.document.getElementById('edusharing_width').value = node.width;
    parent.document.getElementById('edusharing_preview_image').src = getPreviewUrl();
    parent.document.getElementById('edusharing_version_current').value = node.version;
    parent.document.getElementById('edusharing_version_display').textContent = node.version;
    node.ratio = node.width / node.height;
    parent.document.getElementById('edusharing_height').onkeyup = function(event) {
        parent.document.getElementById('edusharing_width').value = (parent.document.getElementById('edusharing_height').value * node.ratio).toFixed();
    };
    parent.document.getElementById('edusharing_width').onkeyup = function(event) {
        parent.document.getElementById('edusharing_height').value = (parent.document.getElementById('edusharing_width').value * node.ratio).toFixed();
    };

}

function getPreviewUrl() {
    var nodeId = node.object_url.split('/').slice(-1).pop();
    return appconfig.repo_url + 'preview?nodeId=' + nodeId + '&ticket=' + ticket;
}

function getInsertHtml() {
    if(node.mimetype.indexOf('video') !== -1 || node.mimetype.indexOf('image') !== -1) {

        var style = 'style="width:' + parent.document.getElementById('edusharing_width').value + 'px;'+
            'height:' + parent.document.getElementById('edusharing_height').value + 'px;';

        switch(parent.document.querySelector('input[name="edusharing_float"]:checked').value) {
            case 'left':
                style += 'float:left;"';
                break;
            case 'right':
                style += 'float:right;"';
                break;
            default:
                style += 'display:inline-block;"';
        }

        return '<img src="' + getPreviewUrl() + '"' +
            'data-edusharing_uid="" ' +
            'data-edusharing_nodeid="' + node.object_url + '" ' +
            'data-edusharing_mimetype="' + node.mimetype + '" ' +
            'data-edusharing_version="' + parent.document.querySelector('input[name="edusharing_version"]:checked').value + '" ' +
            'data-edusharing_title="' + node.title + '" ' +
            style +
            '>';
    } else {
        return '<a href="#" ' +
            'data-edusharing_uid="" ' +
            'data-edusharing_nodeid="' + node.object_url + '" ' +
            'data-edusharing_mimetype="' + node.mimetype + '" ' +
            'data-edusharing_version="' + parent.document.querySelector('input[name="edusharing_version"]:checked').value + '" ' +
            'data-edusharing_title="' + node.title + '" ' +
            '>' + node.title + '</a>';
    }
}

function getModalContent() {
    var reurl = encodeURIComponent(appconfig.app_url + '?eID=edusharing_populate');
    return '' +
        '<iframe id="edusharing_repository_frame" src="' + appconfig.repo_url + 'components/search?ticket=' + ticket + '&reurl=' + reurl + '"></iframe>' +
        '<div id="edusharing_object_dialog">' +
            '<img id="edusharing_preview_image" src="" style="float:right;  ">' +
            '<label for="edusharing_title_display">Titel</label>' +
            '<h1 id="edusharing_title_display"></h1>' +
            '<fieldset id="edusharing_fieldset_dimensions">' +
                '<h2>Dimensionen</h2>'+
                '<div>' +
                    '<label class="edusharing_label">H&ouml;he</label><br/>' +
                    '<input id="edusharing_height" onkeypress="return event.charCode >= 48 && event.charCode <= 57" maxlength="4" size="4"> px' +
                '</div>' +
                '<div>' +
                    '<label class="edusharing_label">Breite</label><br/>' +
                    '<input id="edusharing_width" onkeypress="return event.charCode >= 48 && event.charCode <= 57" maxlength="4" size="4"> px' +
                '</div>' +
            '</fieldset>' +
            '<fieldset id="edusharing_fieldset_float">' +
                '<h2>Ausrichtung</h2>'+
                '<input type="radio" id="edusharing_float_left" name="edusharing_float" value="left">' +
                '<label for="edusharing_float_left">links umflie&szlig;end</label><br/>' +
                '<input type="radio" id="edusharing_float_right" name="edusharing_float" value="right">' +
                '<label for="edusharing_float_right">rechts umflie&szlig;end</label><br/>' +
                '<input type="radio" id="edusharing_float_none" name="edusharing_float" value="none" checked>' +
                '<label for="edusharing_float_none">keine</label>' +
            '</fieldset>' +
            '<fieldset id="edusharing_fieldset_version">' +
                '<h2>Version</h2>'+
                '<span id="edusharing_version_display_text">aktuelle Version: ' + '<span id="edusharing_version_display"></span></span><br/>' +
                '<input type="radio" id="edusharing_version_current" name="edusharing_version" value="">' +
                '<label for="edusharing_version_current">genau diese Version</label><br/>' +
                '<input type="radio" id="edusharing_version_latest" name="edusharing_version" value="-1" checked>' +
                '<label for="edusharing_version_latest">immer die aktuellste</label>' +
            '</fieldset>'
        '</div>';
}
