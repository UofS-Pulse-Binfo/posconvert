/**
 * @file
 * Manage behaviour in posconvert
 */
(function ($) {
  Drupal.behaviors.posconvert = {
    attach: function (context, settings) {
      // Show sample file.
      $('#link-slide').once(function() {
        $(this).click(function(e) {
          // Stop default behaviour of a.
          e.preventDefault();
          // Check the link caption
          var lnk = $(this);
          var l;

          if (lnk.text() == 'Show sample file') {
            $('#div-slide-container').slideDown();
            $('#div-slide-container textarea').scrollTop(0);
            l = 'Hide sample file';
          }
          else {
            $('#div-slide-container').slideUp();
            l = 'Show sample file';
          }

          $(this).text(l);
        });
      });


      // File upload window.
      // Flag used in AJAX process.
      var flag               = 0;
      // Reference upload window form elements.
      // Textarea.
      var txtField           = $('#txt-data-field');
      // Focus textarea on load.
      txtField.focus();

      var bgCopyPaste        = txtField.css('background-image');

      var linkLaunchUpload   = $('#link-upload-file');
      var linkCancel         = $('#link-upload-cancel');
      var btnLoadData        = $('#btn-load-file-data-to-txtfield');
      var divUploadContainer = $('#container-file-upload');
      // Button remove file when a file was successfully uploaded.
      // This botton replaces the upload button.
      var btnAJAXRemove      = $('#edit-file-remove-button');
      // Checkbox to refresh the form and load stage 2.
      var chkReloadPage      = $('#chk-load-file-data-to-txtfield-refresh');
      // Submit/Convert Button.
      var btnConvert         = $('#btn-convert');

      // File uploader.
      linkLaunchUpload.once(function() {
        $(this).click(function(e) {
          e.preventDefault();
          // Hide the link to launch upload window.
          $(this).hide();
          // Show the upload window.
          divUploadContainer.show();
          // Make text field - read only to put focus to upload window.
          txtField.attr('readonly', true);
        });
      });


      // Cancel file uploader.
      linkCancel.once(function() {
        $(this).click(function(e) {
          e.preventDefault();
          if ($('.messages').not('.warning')) {
            $('.messages').not('.warning').remove();
          }

          if (btnLoadData.is(':visible')) {
            divUploadContainer.hide();
            // Reload the page.
            window.location.href = 'posconvert';
          }
          else {
            // Show the link to upload window.
            linkLaunchUpload.show();
            // Hide the upload window.
            divUploadContainer.hide();
            // Make text field - enabled.
            txtField.attr('readonly', false);
            // Focus textarea
            txtField.focus();
          }
        });
      })

      // Manage AJAX calls
      $(document)
      // START
      .ajaxStart(function() {
        if ($('.messages').not('.warning')) {
          $('.messages').not('.warning').remove();
        }

        // When AJAX call is to upload file.
        // When upload file window is active.
        if (divUploadContainer.length) {
          // Load data button is available.
          if (btnLoadData.is(':visible')) {
            // Hide the upload window.
            divUploadContainer.hide();
            // Set the flag to tell AJAX is processing.
            flag = 1;
          }
        }

        // Clear the field on AJAX start.
        txtField.css('background-image', 'none');
        linkLaunchUpload.hide();

        if (btnConvert) {
          btnConvert.attr('disabled', true).addClass('form-button-disabled').hide();
          btnConvert.closest('div').once(function() {
            $(this).append('<span class="win-loading">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Please wait...</span>');
          });

          $('.win-loading').show();
        }
      })
      // END
      .ajaxComplete(function() {
        $('.win-loading').hide();

        // Remove any help text.
        if ($('.messages')) {
          $('.help-text').hide();
        }

        if (divUploadContainer.length) {
          // When button remove becomes available in the DOM.
          // Upload is successful.
          if (btnAJAXRemove.is(':visible')) {
            // Call checkbox to refresh the page.
            btnLoadData.show();
            btnAJAXRemove.hide();
            // Remove the choose file text - might confuse.
            $('#container-file-upload label').hide();
          }
        }

        if (flag == 1) {
          // If data was loaded to the textarea click the refresh
          // checkbox and load stage 02.
          divUploadContainer.hide();
          // AJAX load stage 02 base on the text data parsed from the file.
          chkReloadPage.click();
          // Restart the Upload process.
          flag = 0;
        }
        if (txtField.val()) {
          $('#link-reset-form-container').slideDown();
        }

        // Make field read only and scroll to top of line.
        $('#txt-data-field').attr('readonly', true).scrollTop(0).focus();
        $('#link-upload-container').hide();

        if ($('select')) {
          $('select').attr('disabled', false);
        }

        if ($('#mod-status-clear').length > 0) {
          btnConvert.attr('disabled', false).removeClass('form-button-disabled').show();
        }
        else {
          btnConvert.attr('disabled', true).addClass('form-button-disabled').hide();
        }
      });

      // Add event listener to clear/reset button.
      if ($('#link-reset-form-container')) {
        $('#link-reset-form-container').once(function() {
          $('#link-reset-form-container').click(function(e) {
            // Check to see if there is file that has been uploaded.
            // If so, reload the page instead to reset the form.
            if ($('input[name="file[fid]"]').val() > 0) {
              window.location.href = 'posconvert';
            }
            else {
              // Clear then enable from field.
              $('#txt-data-field').val('').removeAttr('readonly').blur().focus();
              // Add manual upload option link.
              linkLaunchUpload.show();
              // Remove clear button.
              $(this).hide();
              // Hide select boxes, submit button and all.
              $('#ajax-wrapper-load-step02 div, #ajax-wrapper-load-step02 input').hide();

              // Reset select boxes.
              $('select').val(0);
            }
          });
        });
      }

      // Add event listener to textarea.
      txtField.once(function() {
        txtField.focus(function() {
          if (txtField.val() != '') {
            $(this).css('background-image', 'none');
            $('#link-upload-container').hide();
          }
        })
        .blur(function() {
          var bg;

          bg = (txtField.val()) ? 'none' : bgCopyPaste;
          $(this).css('background-image', bg);

          if (bg != 'none') {
            $('#link-upload-container').show();
          }
        });
      });

      // Add event listener to submit button.
      btnConvert.click(function(e) {
        $(this).hide();
        btnConvert.once(function() {
          $(this).closest('div').append('<span class="win-loading">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Please wait...</span>');
          // Remove Reset
          $('#link-reset-form-container').hide();
          // Prevent user from selecting a difference column once form is submitted.
          // Disabling the field, will not submit.
          $('select option:not(:selected)').remove();
        });
      });

      // Add event lister to help buttons.
      $('.help-icon').once(function() {
        $(this).click(function(e) {
          var helpText = $(this).parent().find('.help-text');

          if (helpText.is(':visible')) {
            $(this).parent().find('.help-text').hide();
          }
          else {
            $(this).parent().find('.help-text').show();
          }
        });
      });
    }
  };
}(jQuery));
