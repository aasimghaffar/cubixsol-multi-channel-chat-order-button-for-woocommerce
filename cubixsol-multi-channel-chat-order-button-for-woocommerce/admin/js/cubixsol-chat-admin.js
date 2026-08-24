(function($) {
  'use strict';

  $(document).ready(function() {

    // Tab Navigation
    $('.cubixsol-chat-tab-link').on('click', function(e) {
      e.preventDefault();
      var targetTab = $(this).data('tab');

      $('.cubixsol-chat-tab-link').removeClass('active');
      $('.cubixsol-chat-tab-content').removeClass('active');

      $(this).addClass('active');
      $('#' + targetTab).addClass('active');
    });

    // Agents Repeater: Add
    $('#cubixsol-chat-add-agent').on('click', function(e) {
      e.preventDefault();
      var template = $('#cubixsol-chat-agent-template').html();
      if (!template) {
        return;
      }
      var index = new Date().getTime(); // Unique index; PHP re-keys on save.
      $('#cubixsol-chat-agents-list').append(template.replace(/__INDEX__/g, index));
    });

    // Agents Repeater: Remove
    $(document).on('click', '.cubixsol-chat-remove-agent', function(e) {
      e.preventDefault();
      $(this).closest('.cubixsol-chat-agent-box').remove();
    });

    // Filter Pills
    $('.cubixsol-chat-filter-pill').on('click', function(e) {
      e.preventDefault();
      var filter = $(this).data('filter');
      $('.cubixsol-chat-filter-pill').removeClass('active');
      $(this).addClass('active');

      if (filter === 'all') {
        $('.cart-item-row').show();
      } else {
        $('.cart-item-row').hide();
        $('.cart-item-row[data-status="' + filter + '"]').show();
      }
    });

    // Live Search
    $('#cubixsol-chat-table-search').on('keyup', function() {
      var query = $(this).val().toLowerCase();
      $('.cart-item-row').each(function() {
        var rowText = $(this).text().toLowerCase();
        $(this).toggle(rowText.indexOf(query) !== -1);
      });
    });

    // Mark Cart as Recovered (AJAX — actually persists in DB)
    $(document).on('click', '.cubixsol-chat-mark-recovered-btn', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var cartId = $btn.data('cart-id');

      if (!cartId || typeof cubixsolChatAdminData === 'undefined') {
        return;
      }
      if (cubixsolChatAdminData.i18n && !window.confirm(cubixsolChatAdminData.i18n.confirmRecovered)) {
        return;
      }

      $btn.prop('disabled', true);

      $.post(cubixsolChatAdminData.ajaxUrl, {
        action: 'cubixsol_chat_mark_recovered',
        nonce: cubixsolChatAdminData.nonce,
        cart_id: cartId
      }).done(function(res) {
        if (res && res.success) {
          var $row = $btn.closest('.cart-item-row');
          $row.attr('data-status', 'recovered');
          $row.find('.lead-status')
            .removeClass('cubixsol-chat-badge-abandoned')
            .addClass('cubixsol-chat-badge-recovered')
            .text('Recovered ✓');
          $row.find('td:last').html('<span class="cubixsol-chat-recovered-check">✓ Recovered</span>');
        } else {
          window.alert((res && res.data && res.data.message) || cubixsolChatAdminData.i18n.error);
          $btn.prop('disabled', false);
        }
      }).fail(function() {
        window.alert(cubixsolChatAdminData.i18n.error);
        $btn.prop('disabled', false);
      });
    });

    // Send recovery message via Meta Cloud API (per-row button)
    $(document).on('click', '.cubixsol-chat-api-send-btn', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var cartId = $btn.data('cart-id');

      if (!cartId || typeof cubixsolChatAdminData === 'undefined') {
        return;
      }
      if (cubixsolChatAdminData.i18n && !window.confirm(cubixsolChatAdminData.i18n.confirmApiSend)) {
        return;
      }

      var originalText = $btn.text();
      $btn.prop('disabled', true).text(cubixsolChatAdminData.i18n.sending);

      $.post(cubixsolChatAdminData.ajaxUrl, {
        action: 'cubixsol_chat_send_recovery_now',
        nonce: cubixsolChatAdminData.nonce,
        cart_id: cartId
      }).done(function(res) {
        if (res && res.success) {
          $btn.replaceWith('<span class="cubixsol-chat-api-sent-tag">' + cubixsolChatAdminData.i18n.sent + '</span>');
        } else {
          window.alert((res && res.data && res.data.message) || cubixsolChatAdminData.i18n.error);
          $btn.prop('disabled', false).text(originalText);
        }
      }).fail(function() {
        window.alert(cubixsolChatAdminData.i18n.error);
        $btn.prop('disabled', false).text(originalText);
      });
    });

    // Meta API: send test message from the settings page
    $(document).on('click', '#cubixsol-chat-send-test-btn', function(e) {
      e.preventDefault();
      var $btn = $(this);
      var phone = $('#cubixsol-chat-test-phone').val();
      var $result = $('#cubixsol-chat-test-result');

      if (!phone || typeof cubixsolChatAdminData === 'undefined') {
        return;
      }

      $btn.prop('disabled', true);
      $result.css('color', '').text(cubixsolChatAdminData.i18n.sending);

      $.post(cubixsolChatAdminData.ajaxUrl, {
        action: 'cubixsol_chat_meta_send_test',
        nonce: cubixsolChatAdminData.nonce,
        phone: phone
      }).done(function(res) {
        if (res && res.success) {
          $result.css('color', '#00a32a').text(res.data.message);
        } else {
          $result.css('color', '#d63638').text((res && res.data && res.data.message) || cubixsolChatAdminData.i18n.error);
        }
      }).fail(function() {
        $result.css('color', '#d63638').text(cubixsolChatAdminData.i18n.error);
      }).always(function() {
        $btn.prop('disabled', false);
      });
    });

    // CSV Export Generator
    $('#cubixsol-chat-export-csv-btn').on('click', function(e) {
      e.preventDefault();

      var csvRows = [];
      csvRows.push(['Customer Name', 'Email', 'Phone Number', 'Cart Items', 'Category', 'Total Amount', 'Status'].join(','));

      $('#cubixsol-chat-leads-table tbody tr').each(function() {
        var $row = $(this);
        var cells = [
          $row.find('.lead-name').text(),
          $row.find('.lead-email').text(),
          $row.find('.lead-phone').text(),
          $row.find('.lead-item').text(),
          $row.find('.lead-category').text(),
          $row.find('.lead-total').text(),
          $row.find('.lead-status').text()
        ].map(function(v) {
          return '"' + String(v).trim().replace(/"/g, '""') + '"';
        });
        csvRows.push(cells.join(','));
      });

      var csvString = csvRows.join('\n');
      var blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
      var url = URL.createObjectURL(blob);
      var link = document.createElement('a');

      var today = new Date().toISOString().slice(0, 10);
      link.setAttribute('href', url);
      link.setAttribute('download', 'cubixsol-chat-abandoned-leads-' + today + '.csv');
      link.style.visibility = 'hidden';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    });
  });

})(jQuery);
