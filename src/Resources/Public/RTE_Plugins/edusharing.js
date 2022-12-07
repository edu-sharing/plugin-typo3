'use strict';

// Promises as cache for config and ticket. Don't access these variables directly, but use the
// functions `getAppConfig` and `getTicket`.
let _appConfigPromise = null;
let _ticketPromise = null;
var node = {};
var repositoryTab;

CKEDITOR.plugins.add('edusharing', {
    lang: ['de'],
    icons: 'edusharing',
    init: function (editor) {
        editor.on('instanceReady', function (event) {
            $(editor.document.$.body)
                .find('[data-edusharing_node-id]')
                .each(function () {
                    setPreviewUrl(this);
                });
        });

        editor.ui.addButton('edusharing', {
            label: editor.lang.edusharing.buttonlabel,
            tooolbar: 'basicstyles',
            command: 'openModal',
            // We could consider migrating from our `<img>` elements with custom `data-edu-sharing`
            // attributes to custom `<edu-sharing-object>` elements with a `node-id` and style
            // attributes, but we would need to
            // - adapt our handling of styles (at least for the editor view)
            // - migrate existing `img` elements
            // - fetch additional data from the database
            //
            // allowedContent: 'edu-sharing-object[node-id]', requiredContent:
            // 'edu-sharing-object[node-id]',
        });

        // Prevent opening of image plugin dialog and insert edu dialog instead
        editor.on('doubleclick', function (evt) {
            if (evt.data.element.getAttribute('data-edusharing_node-id')) {
                delete evt.data.dialog;
                editor.execCommand('openModal');
            }
        });

        editor.addCommand('openModal', {
            exec: function (editor) {
                require(['TYPO3/CMS/Backend/Modal'], function (Modal) {
                    var button_apply_text = editor.lang.edusharing.insert;
                    var selection =
                        editor.getSelection().getSelectedElement() ||
                        editor.getSelection().getStartElement();
                    if (selection && selection.getAttribute('data-edusharing_node-id')) {
                        button_apply_text = editor.lang.edusharing.apply_changes;
                        setNode(selection);
                        var html = getEditDialogHtml(editor);
                        if (
                            selection.getAttribute('data-edusharing_mediatype') === 'saved_search'
                        ) {
                            setSavedSearchPreview(editor);
                        }
                        bindAction(editor);
                    } else {
                        var html = getInsertDialogHtml(editor);
                        openRepositoryTab();
                        bindAction(editor);
                    }

                    var configuration = {
                        title: 'edu-sharing',
                        content: $('<div class="dialog-container">').html(html),
                        size: Modal.sizes.large,
                        buttons: [
                            {
                                text: button_apply_text,
                                active: true,
                                trigger: async function () {
                                    if (!node?.id) {
                                        require(['TYPO3/CMS/Backend/Notification'], (
                                            Notification,
                                        ) => {
                                            Notification.notice(
                                                editor.lang.edusharing.choose_element_notice,
                                            );
                                        });
                                        return;
                                    }
                                    await insertEduObject(editor);
                                    node = {};
                                    Modal.dismiss();
                                },
                            },
                            {
                                text: editor.lang.edusharing.cancel,
                                trigger: function () {
                                    node = {};
                                    Modal.dismiss();
                                },
                            },
                        ],
                        callback: function (Modal) {
                            parent.window.addEventListener(
                                'message',
                                function handleMessage(event) {
                                    if (event.data.event == 'APPLY_NODE') {
                                        node.id = event.data.data.ref.id;
                                        node.title = event.data.data.name;
                                        if (event.data.data.title && event.data.data.title.length) {
                                            node.title = event.data.data.title;
                                        }
                                        if (event.data.data.properties['ccm:height']) {
                                            node.height =
                                                event.data.data.properties['ccm:height'][0];
                                        }
                                        if (event.data.data.properties['ccm:width']) {
                                            node.width = event.data.data.properties['ccm:width'][0];
                                        }
                                        if (event.data.data.properties['cclom:version']) {
                                            node.version =
                                                event.data.data.properties['cclom:version'][0];
                                        }
                                        node.mimetype = event.data.data.mimetype;
                                        node.mediatype = event.data.data.mediatype;
                                        parent.window.removeEventListener(
                                            'message',
                                            handleMessage,
                                            false,
                                        );
                                        applyValues(editor);
                                    }
                                },
                                false,
                            );
                        },
                    };
                    Modal.advanced(configuration);
                });
            },
        });
    },
});

async function getAppConfig() {
    if (!_appConfigPromise) {
        _appConfigPromise = new Promise((resolve, reject) => {
            $.ajax({
                url: TYPO3.settings.ajaxUrls['getAppconfig'],
                method: 'GET',
                dataType: 'json',
                success: resolve,
                error: function (xhr, errorType, error) {
                    console.error('Failed to get app config: ' + error);
                    reject(error);
                },
            });
        });
    }
    return _appConfigPromise;
}

async function getTicket() {
    if (!_ticketPromise) {
        _ticketPromise = new Promise((resolve, reject) => {
            $.ajax({
                url: TYPO3.settings.ajaxUrls['getTicket'],
                method: 'GET',
                cache: false,
                success: resolve,
                error: function (xhr, errorType, error) {
                    console.error('Failed to get ticket: ' + error);
                    reject(error);
                },
            });
        });
    }
    return _ticketPromise;
}

async function setPreviewUrl(element) {
    element.src = await getPreviewUrl(element.getAttribute('data-edusharing_node-id'));
}

function bindAction(editor) {
    $(parent.document).on('change', '#edusharing_savedsearch_limit', function () {
        node.savedsearch_limit = this.value;
        setSavedSearchPreview(editor);
    });
    $(parent.document).on('change', '#edusharing_savedsearch_order', function () {
        node.savedsearch_sortproperty = this.value;
        setSavedSearchPreview(editor);
    });
    $(parent.document).on('change', '#edusharing_savedsearch_template', function () {
        node.savedsearch_template = this.value;
        setSavedSearchPreview(editor);
    });
    $(parent.document).on('click', '#edusharing_savedsearch_repick_a', async function () {
        openRepositoryTab(`&savedQuery=${encodeURIComponent(node.id)}`);
    });

    $(parent.document).on('keyup', '#edusharing_height', function () {
        parent.document.getElementById('edusharing_width').value = (
            parent.document.getElementById('edusharing_height').value * node.ratio
        ).toFixed();
    });

    $(parent.document).on('keyup', '#edusharing_width', function () {
        parent.document.getElementById('edusharing_height').value = (
            parent.document.getElementById('edusharing_width').value / node.ratio
        ).toFixed();
    });
}

/**
 * Prepares the edu-sharing dialog to edit the selected element instead of inserting a new one.
 */
function setNode(selection) {
    node.uid = selection.getAttribute('data-edusharing_uid');
    node.id = selection.getAttribute('data-edusharing_node-id');
    node.title = selection.getAttribute('data-edusharing_title');
    node.height = selection.getComputedStyle('height').replace('px', '');
    node.width = selection.getComputedStyle('width').replace('px', '');
    node.ratio = node.width / node.height;
    node.src = selection.getAttribute('src');
    node.version = selection.getAttribute('data-edusharing_version');
    node.mimetype = selection.getAttribute('data-edusharing_mimetype');
    node.mediatype = selection.getAttribute('data-edusharing_mediatype');
    node.float = selection.getComputedStyle('float');
    node.savedsearch_limit = selection.getAttribute('data-edusharing_savedsearch_limit');
    node.savedsearch_sortproperty = selection.getAttribute(
        'data-edusharing_savedsearch_sortproperty',
    );
    node.savedsearch_template = selection.getAttribute('data-edusharing_savedsearch_template');
}

async function applyValues(editor) {
    repositoryTab?.close();
    repositoryTab = null;
    const chooseElementNotice = parent.document.getElementById(
        'edusharing_choose_element_notice_container',
    );
    if (chooseElementNotice) {
        chooseElementNotice.style.display = 'none';
    }
    parent.document.getElementById('edusharing_object_dialog').style.display = 'block';
    parent.document.getElementById('edusharing_fieldset_savedsearch').style.display = 'none';
    parent.document.getElementById('edusharing_title_display').textContent = node.title;

    if (node.mediatype.indexOf('video') > -1 || node.mediatype.indexOf('image') > -1) {
        parent.document.getElementById('edusharing_preview_image').src = await getPreviewUrl();
        parent.document.getElementById('edusharing_height').value = node.height;
        parent.document.getElementById('edusharing_width').value = node.width;
        node.ratio = node.width / node.height;
    } else {
        if (parent.document.getElementById('edusharing_fieldset_dimensions'))
            parent.document.getElementById('edusharing_fieldset_dimensions').style.display = 'none';
        if (parent.document.getElementById('edusharing_fieldset_float'))
            parent.document.getElementById('edusharing_fieldset_float').style.display = 'none';
    }

    if (node.mediatype == 'saved_search') {
        if (parent.document.getElementById('edusharing_preview_image'))
            parent.document.getElementById('edusharing_preview_image').style.display = 'none';
        if (parent.document.getElementById('edusharing_fieldset_version'))
            parent.document.getElementById('edusharing_fieldset_version').style.display = 'none';
        parent.document.getElementById('edusharing_fieldset_savedsearch').style.display = 'block';

        node.savedsearch_limit = parent.document.getElementById(
            'edusharing_savedsearch_limit',
        ).value;
        node.savedsearch_sortproperty = parent.document.getElementById(
            'edusharing_savedsearch_order',
        ).value;
        node.savedsearch_template = parent.document.getElementById(
            'edusharing_savedsearch_template',
        ).value;
        setSavedSearchPreview(editor);
    } else {
        parent.document.getElementById('edusharing_version_current').value = node.version;
        parent.document.getElementById('edusharing_version_display').textContent = node.version;
    }
}

function setSavedSearchPreview(editor) {
    $.ajax({
        url: TYPO3.settings.ajaxUrls['getSavedSearch'],
        method: 'POST',
        dataType: 'json',
        data: {
            nodeId: node.id,
            maxItems: node.savedsearch_limit,
            skipCount: 0,
            sortProperty: node.savedsearch_sortproperty,
            template: node.savedsearch_template,
        },
        success: async function (response) {
            const previewContainer = await waitForElement(
                parent?.document ?? document,
                '#edusharing_savedsearch_preview',
            );
            previewContainer.innerHTML = JSON.parse(response);
        },
        error: function () {
            if (parent) {
                parent.document.getElementById('edusharing_savedsearch_preview').innerHTML =
                    JSON.parse(response);
            } else {
                document.getElementById('edusharing_savedsearch_preview').innerHTML =
                    editor.lang.edusharing.saved_search_preview_error;
            }
        },
    });
}

async function getPreviewUrl(nodeId = node.id) {
    // FIXME: This function is used by insertEduObject to create a database entry. When doing this,
    // the ticket is persisted in the database and the preview image cannot be loaded once the
    // ticket expires.
    //
    // TODO: Figure out how to grant permissions to frontend users, who don't provide any
    // authentication.
    return (
        `${(await getAppConfig()).repo_url}preview` +
        `?nodeId=${encodeURIComponent(nodeId)}` +
        `&ticket=${encodeURIComponent(await getTicket())}`

        // Including the ticket poses the problems described above. By omitting it, an authenticated
        // user can access the preview via cookie authentication and not-authenticated users can
        // access public preview image, where an expired ticket will lead to a failed authentication
        // in any case.
    );
}

/**
 * Called after the edu-sharing dialog is confirmed.
 *
 * Inserts a new edu-sharing element into the editor content or updates an existing one.
 */
async function insertEduObject(editor) {
    if (node.mediatype.indexOf('video') !== -1 || node.mediatype.indexOf('image') !== -1) {
        var obj = editor.document.createElement('img');
        switch (parent.document.querySelector('input[name="edusharing_float"]:checked').value) {
            case 'left':
                obj.setStyle('float', 'left');
                break;
            case 'right':
                obj.setStyle('float', 'right');
                break;
            default:
                obj.setStyle('display', 'inline-block');
        }
        obj.setStyle('width', parent.document.getElementById('edusharing_width').value + 'px');
        obj.setStyle('height', parent.document.getElementById('edusharing_height').value + 'px');
        obj.setAttribute('src', await getPreviewUrl());
    } else if (node.mediatype == 'saved_search') {
        var obj = editor.document.createElement('img');
        obj.setStyle('display', 'block');
        obj.setStyle('width', 'auto');
        obj.setStyle('height', '300px');
        obj.setAttribute('src', await getPreviewUrl());
        obj.setAttribute('data-edusharing_savedsearch_limit', node.savedsearch_limit);
        obj.setAttribute('data-edusharing_savedsearch_sortproperty', node.savedsearch_sortproperty);
        obj.setAttribute('data-edusharing_savedsearch_template', node.savedsearch_template);
    } else {
        var obj = editor.document.createElement('var');
        obj.setHtml(node.title);
    }

    obj.contentEditable = true;

    if (!node.uid) {
        obj.setAttribute('data-edusharing_uid', '');
    } else {
        obj.setAttribute('data-edusharing_uid', node.uid);
        obj.setAttribute('data-edusharing_edited', 'true');
    }

    obj.setAttribute('data-edusharing_node-id', node.id);
    obj.setAttribute('data-edusharing_mimetype', node.mimetype);
    obj.setAttribute('data-edusharing_mediatype', node.mediatype);

    if (parent.document.querySelector('input[name="edusharing_version"]:checked'))
        obj.setAttribute(
            'data-edusharing_version',
            parent.document.querySelector('input[name="edusharing_version"]:checked').value,
        );
    else obj.setAttribute('data-edusharing_version', node.version);

    obj.setAttribute('data-edusharing_title', node.title);

    editor.insertElement(obj);
}

function getEditDialogHtml(editor) {
    if (node.mimetype.indexOf('video') >= 0 || node.mimetype.indexOf('image') >= 0) {
        var html =
            '<div id="edusharing_object_dialog">' +
            '<img id="edusharing_preview_image" src="' +
            node.src +
            '">' +
            '<label for="edusharing_title_display" id="edusharing_title_display_label">' +
            editor.lang.edusharing.title +
            '</label>' +
            '<h1 id="edusharing_title_display">' +
            node.title +
            '</h1>' +
            '<fieldset id="edusharing_fieldset_dimensions">' +
            '<h2>' +
            editor.lang.edusharing.dimensions +
            '</h2>' +
            '<div>' +
            '<label class="edusharing_label">' +
            editor.lang.edusharing.height +
            '</label><br/>' +
            '<input value="' +
            node.height +
            '" id="edusharing_height" onkeypress="return event.charCode >= 48 && event.charCode <= 57" maxlength="4" size="4"> px' +
            '</div>' +
            '<div>' +
            '<label class="edusharing_label">' +
            editor.lang.edusharing.width +
            '</label><br/>' +
            '<input value="' +
            node.width +
            '" id="edusharing_width" onkeypress="return event.charCode >= 48 && event.charCode <= 57" maxlength="4" size="4"> px' +
            '</div>' +
            '</fieldset>';

        html +=
            '<fieldset id="edusharing_fieldset_float">' +
            '<h2>' +
            editor.lang.edusharing.float +
            '</h2>' +
            '<input type="radio" id="edusharing_float_left" name="edusharing_float" value="left" ' +
            (node.float == 'left' ? 'checked' : '') +
            '>' +
            '<label for="edusharing_float_left">' +
            editor.lang.edusharing.float_left +
            '</label><br/>' +
            '<input type="radio" id="edusharing_float_right" name="edusharing_float" value="right" ' +
            (node.float == 'right' ? 'checked' : '') +
            '>' +
            '<label for="edusharing_float_right">' +
            editor.lang.edusharing.float_right +
            '</label><br/>' +
            '<input type="radio" id="edusharing_float_none" name="edusharing_float" value="none" ' +
            (node.float != 'left' && node.float != 'right' ? 'checked' : '') +
            '>' +
            '<label for="edusharing_float_none">' +
            editor.lang.edusharing.float_none +
            '</label>' +
            '</fieldset>';
    } else if (node.mediatype != 'saved_search') {
        var html =
            '<div id="edusharing_object_dialog">' +
            '<img id="edusharing_preview_image" src="' +
            node.src +
            '">' +
            '<label for="edusharing_title_display" id="edusharing_title_display_label">' +
            editor.lang.edusharing.title +
            '</label>' +
            '<h1 id="edusharing_title_display">' +
            node.title +
            '</h1>';
    }

    if (node.mediatype == 'saved_search') {
        // FIXME: iframe won't work anymore.
        var html =
            '<div id="edusharing_object_dialog">' +
            '<label for="edusharing_title_display" id="edusharing_title_display_label">' +
            editor.lang.edusharing.title +
            '</label>' +
            '<h1 id="edusharing_title_display">' +
            node.title +
            '</h1>' +
            '<fieldset id="edusharing_fieldset_savedsearch">' +
            '<div id="edusharing_savedsearch_preview"></div>' +
            '<div id="edusharing_savedsearch_repick"><a id="edusharing_savedsearch_repick_a" href="#">' +
            editor.lang.edusharing.savedsearch_edit +
            '</a></div>' +
            '<div id="edusharing_savedsearch_settings">' +
            '<h2>' +
            editor.lang.edusharing.advancedsettings +
            '</h2>' +
            '<label for="edusharing_savedsearch_limit">' +
            editor.lang.edusharing.savedsearch_limit_a +
            '</label>' +
            '<select id="edusharing_savedsearch_limit" name="edusharing_savedsearch_limit">' +
            '<option value="5" ' +
            (node.savedsearch_limit == '5' ? 'selected="selected"' : '') +
            '>5</option><option value="10" ' +
            (node.savedsearch_limit == '10' ? 'selected="selected"' : '') +
            '>10</option><option value="20" ' +
            (node.savedsearch_limit == '20' ? 'selected="selected"' : '') +
            '>20</option><option value="10000" ' +
            (node.savedsearch_limit == '10000' ? 'selected="selected"' : '') +
            '>' +
            editor.lang.edusharing.savedsearch_limit_all +
            '</option>' +
            '</select> ' +
            editor.lang.edusharing.savedsearch_limit_b +
            '<label for="edusharing_savedsearch_order">' +
            editor.lang.edusharing.savedsearch_order_a +
            '</label>' +
            '<select id="edusharing_savedsearch_order" name="edusharing_savedsearch_order"><option value="cm:modified">' +
            editor.lang.edusharing.savedsearch_order_modified +
            '</option></select> ' +
            editor.lang.edusharing.savedsearch_order_c +
            '<label for="edusharing_savedsearch_template">' +
            editor.lang.edusharing.savedsearch_template +
            ':</label>' +
            '<select id="edusharing_savedsearch_template" name="edusharing_savedsearch_template"><option value="card" ' +
            (node.savedsearch_template == 'card' ? 'selected="selected"' : '') +
            '>' +
            editor.lang.edusharing.savedsearch_template_card +
            '</option><option value="list" ' +
            (node.savedsearch_template == 'list' ? 'selected="selected"' : '') +
            '>' +
            editor.lang.edusharing.savedsearch_template_list +
            '</option></select>' +
            '</div>' +
            '</fieldset>';
    } else {
        html +=
            '<fieldset id="edusharing_fieldset_version" style="display: none">' +
            '<h2>' +
            editor.lang.edusharing.version +
            '</h2>' +
            '<span id="edusharing_version_display_text">' +
            editor.lang.edusharing.version_display +
            ': ' +
            '<span id="edusharing_version_display"></span></span><br/>' +
            '<input type="radio" id="edusharing_version_current" name="edusharing_version" value="' +
            (node.version != '-1' ? node.version : '') +
            '" ' +
            (node.version != '-1' ? 'checked' : '') +
            '>' +
            '<label for="edusharing_version_current">' +
            editor.lang.edusharing.version_current +
            '</label><br/>' +
            '<input type="radio" id="edusharing_version_latest" name="edusharing_version" value="-1" ' +
            (node.version == '-1' ? 'checked' : '') +
            '>' +
            '<label for="edusharing_version_latest">' +
            editor.lang.edusharing.version_latest +
            '</label>' +
            '</fieldset>';
    }
    html += '</div>';
    return html;
}

async function openRepositoryTab(additionalParams = '') {
    repositoryTab = parent.window.open(
        `${(await getAppConfig()).repo_url}components/search` +
            `?ticket=${encodeURIComponent(await getTicket())}` +
            '&reurl=WINDOW' +
            additionalParams,
        'repository',
    );
}

parent.window.focusRepositoryTab = () => {
    if (repositoryTab && !repositoryTab.closed) {
        parent.window.open('', 'repository');
    } else {
        openRepositoryTab();
    }
};

function getInsertDialogHtml(editor) {
    var html =
        '<div id="edusharing_choose_element_notice_container">' +
        '<p id=edusharing_choose_element_notice>' +
        editor.lang.edusharing.choose_element_notice +
        '</p>' +
        '<button onclick="focusRepositoryTab()" type="button">' +
        editor.lang.edusharing.go_to_edu_sharing +
        '</button>' +
        '</div>' +
        '<div id="edusharing_object_dialog" style="display:none">' +
        '<img id="edusharing_preview_image" src="">' +
        '<label for="edusharing_title_display" id="edusharing_title_display_label">' +
        editor.lang.edusharing.title +
        '</label>' +
        '<h1 id="edusharing_title_display"></h1>' +
        '<fieldset id="edusharing_fieldset_dimensions">' +
        '<h2>' +
        editor.lang.edusharing.dimensions +
        '</h2>' +
        '<div>' +
        '<label class="edusharing_label">' +
        editor.lang.edusharing.height +
        '</label><br/>' +
        '<input id="edusharing_height" onkeypress="return event.charCode >= 48 && event.charCode <= 57" maxlength="4" size="4"> px' +
        '</div>' +
        '<div>' +
        '<label class="edusharing_label">' +
        editor.lang.edusharing.width +
        '</label><br/>' +
        '<input id="edusharing_width" onkeypress="return event.charCode >= 48 && event.charCode <= 57" maxlength="4" size="4"> px' +
        '</div>' +
        '</fieldset>' +
        '<fieldset id="edusharing_fieldset_float">' +
        '<h2>' +
        editor.lang.edusharing.float +
        '</h2>' +
        '<input type="radio" id="edusharing_float_left" name="edusharing_float" value="left">' +
        '<label for="edusharing_float_left">' +
        editor.lang.edusharing.float_left +
        '</label><br/>' +
        '<input type="radio" id="edusharing_float_right" name="edusharing_float" value="right">' +
        '<label for="edusharing_float_right">' +
        editor.lang.edusharing.float_right +
        '</label><br/>' +
        '<input type="radio" id="edusharing_float_none" name="edusharing_float" value="none" checked>' +
        '<label for="edusharing_float_none">' +
        editor.lang.edusharing.float_none +
        '</label>' +
        '</fieldset>' +
        '<fieldset id="edusharing_fieldset_version">' +
        '<h2>' +
        editor.lang.edusharing.version +
        '</h2>' +
        '<span id="edusharing_version_display_text">' +
        editor.lang.edusharing.version_display +
        ': ' +
        '<span id="edusharing_version_display"></span></span><br/>' +
        '<input type="radio" id="edusharing_version_current" name="edusharing_version" value="">' +
        '<label for="edusharing_version_current">' +
        editor.lang.edusharing.version_current +
        '</label><br/>' +
        '<input type="radio" id="edusharing_version_latest" name="edusharing_version" value="-1" checked>' +
        '<label for="edusharing_version_latest">' +
        editor.lang.edusharing.version_latest +
        '</label>' +
        '</fieldset>' +
        '<fieldset id="edusharing_fieldset_savedsearch">' +
        '<div id="edusharing_savedsearch_preview"></div>' +
        '<div id="edusharing_savedsearch_repick"><a href="#">' +
        editor.lang.edusharing.savedsearch_edit +
        '</a></div>' +
        '<div id="edusharing_savedsearch_settings">' +
        '<h2>' +
        editor.lang.edusharing.advancedsettings +
        '</h2>' +
        '<label for="edusharing_savedsearch_limit">' +
        editor.lang.edusharing.savedsearch_limit_a +
        '</label>' +
        '<select id="edusharing_savedsearch_limit" name="edusharing_savedsearch_limit">' +
        '<option value="5">5</option><option value="10">10</option><option value="20">20</option><option value="10000">' +
        editor.lang.edusharing.savedsearch_limit_all +
        '</option>' +
        '</select> ' +
        editor.lang.edusharing.savedsearch_limit_b +
        '<label for="edusharing_savedsearch_order">' +
        editor.lang.edusharing.savedsearch_order_a +
        '</label>' +
        '<select id="edusharing_savedsearch_order" name="edusharing_savedsearch_order"><option value="cm:modified">' +
        editor.lang.edusharing.savedsearch_order_modified +
        '</option></select> ' +
        editor.lang.edusharing.savedsearch_order_c +
        '<label for="edusharing_savedsearch_template">' +
        editor.lang.edusharing.savedsearch_template +
        ':</label>' +
        '<select id="edusharing_savedsearch_template" name="edusharing_savedsearch_template"><option value="card">' +
        editor.lang.edusharing.savedsearch_template_card +
        '</option><option value="list">' +
        editor.lang.edusharing.savedsearch_template_list +
        '</option></select>' +
        '</div>' +
        '</fieldset>' +
        '</div>';

    return html;
}

// From https://stackoverflow.com/questions/5525071/how-to-wait-until-an-element-exists/61511955#61511955
function waitForElement(document, selector) {
    return new Promise((resolve) => {
        if (document.querySelector(selector)) {
            return resolve(document.querySelector(selector));
        }

        const observer = new MutationObserver((mutations) => {
            if (document.querySelector(selector)) {
                resolve(document.querySelector(selector));
                observer.disconnect();
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    });
}
