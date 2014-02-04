var IpConfig;

(function ($) {
    "use strict";

    $(document).ready(function () {

        IpConfig.init();

        $('.ipsAutoSave').on('change', IpConfig.autoSaveValue);
        $('.ipsAutoSave').on('keyup', IpConfig.autoSaveValue);

        $('.ipsConfigForm').validator(validatorConfig);
        $('.ipsConfigForm').data("validator").checkValidity();

    });

    IpConfig = new function () {
        var queue = [],
            processing = false;

        this.init = function () {
            updateCronUrl();
        }

        var queueAdd = function (fieldid) {
            queue = removeFromArray(queue, fieldid);
            queue.push(fieldid);
            queueProcess();
        };

        var getFieldValue = function (fieldid) {
            var $field = $('#' + fieldid);
            if ($field.attr('type') === 'checkbox') {
                return $field.prop('checked') ? 1 : 0;
            }

            return $field.val();
        }

        var getFieldLanguage = function (fieldid) {
            var $field = $('#' + fieldid);
            return $field.data('languageid');
        }

        var getFieldName = function (fieldid) {
            var $field = $('#' + fieldid);
            return $field.data('fieldname');
        }

        var queueProcess = function () {
            if (processing) {
                return;
            }
            processing = true;
            var fieldId = queue.shift();

            if (!fieldId) {
                processing = false;
                return;
            }

            updateCronUrl();


            var postData = {
                'aa': 'Config.saveValue',
                'fieldName': getFieldName(fieldId),
                'value': getFieldValue(fieldId),
                'securityToken': ip.securityToken,
                'languageId': getFieldLanguage(fieldId)
            };
            $.ajax({
                url: '',
                data: postData,
                dataType: 'json',
                type: 'POST',
                success: function (response) {
                    if (response && response.error) {
                        var errors = {};
                        errors[fieldId] = response.error;
                        $('.ipsConfigForm').data("validator").invalidate(errors);
                    }
                    processing = false;
                    queueProcess();
                },
                error: function (response) {
                    if (response && response.readyState === 4) {
                        if (response.responseText) {
                            alert(response.responseText);
                        } else {
                            alert('Autosave error');
                        }
                    }
                    processing = false;
                    queueProcess();
                }
            });
        }

        var updateCronUrl = function () {
            var $note = $('.name-cronPassword .ipsUrlLabel');
            var $urlText = $('.name-cronPassword .ipsUrl');
            // cron should work without password for admin
            var $passField = $('.name-cronPassword');
            if (getFieldValue('automaticCron')) {
                $note.addClass('hide');
                $urlText.addClass('hide');
                $passField.addClass('hide');

            } else {
                $note.removeClass('hide');
                var url = ip.baseUrl + '?pa=Cron&pass=' + $('#cronPassword').val();
                $urlText.text(url);
                $urlText.attr('href', url);
                $passField.removeClass('hide');
                $urlText.removeClass('hide');
            }
        }

        this.autoSaveValue = function () {
            var $this = $(this);
            $('.ipsConfigForm').data("validator").checkValidity();
            queueAdd($this.data('fieldid'));
        };

        var removeFromArray = function (dataArray, value) {
            for (var i = dataArray.length - 1; i >= 0; i--) {
                if (dataArray[i] === value) {
                    dataArray.splice(i, 1);
                }
            }
            return dataArray;
        }
    };
})(ip.jQuery);