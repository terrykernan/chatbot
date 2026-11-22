(function ($) {
  'use strict';

  $(function () {

    // Colour picker
    $('.pc-color-picker').wpColorPicker();

    // Toggle transcript
    $(document).on('click', '.pc-toggle-transcript', function () {
      const btn = $(this);
      const id  = btn.data('id');
      const row = $('#pc-transcript-' + id);

      if (row.is(':visible')) {
        row.slideUp(150);
        btn.text('View');
        return;
      }

      if (row.html().trim() !== '') {
        row.slideDown(150);
        btn.text('Hide');
        return;
      }

      btn.text('Loading…');

      $.post(PcAdmin.ajaxUrl, {
        action: 'pc_get_transcript',
        nonce: PcAdmin.nonce,
        conversation_id: id,
      }, function (res) {
        if (res.success) {
          row.html(res.data.html || '<em>No messages found.</em>');
          row.slideDown(150);
          btn.text('Hide');
        } else {
          btn.text('View');
        }
      });
    });

    // Resolve conversation
    $(document).on('click', '.pc-resolve-btn', function () {
      const btn = $(this);
      const id  = btn.data('id');
      if (!confirm('Mark this conversation as resolved?')) return;

      $.post(PcAdmin.ajaxUrl, {
        action: 'pc_resolve_conversation',
        nonce: PcAdmin.nonce,
        conversation_id: id,
      }, function (res) {
        if (res.success) {
          btn.closest('tr').find('.pc-status-badge').removeClass().addClass('pc-badge pc-badge-resolved').text('Resolved');
          btn.remove();
        }
      });
    });

    // Delete conversation
    $(document).on('click', '.pc-delete-btn', function () {
      const btn = $(this);
      const id  = btn.data('id');
      if (!confirm('Permanently delete this conversation and all its messages?')) return;

      $.post(PcAdmin.ajaxUrl, {
        action: 'pc_delete_conversation',
        nonce: PcAdmin.nonce,
        conversation_id: id,
      }, function (res) {
        if (res.success) {
          btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
        }
      });
    });

    // Toggle provider-specific key fields
    function toggleProviderFields() {
      const provider = $('#WOO_CHATBOT_provider').val();
      $('.pc-provider-fields').hide();
      $('.pc-provider-fields[data-provider="' + provider + '"]').show();
    }
    $('#WOO_CHATBOT_provider').on('change', toggleProviderFields);
    toggleProviderFields();

  });
}(jQuery));
