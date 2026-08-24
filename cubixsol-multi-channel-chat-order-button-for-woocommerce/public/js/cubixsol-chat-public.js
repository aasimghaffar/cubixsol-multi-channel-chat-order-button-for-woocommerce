(function($) {
  'use strict';

  $(document).ready(function() {
    // Floating Widget Toggle
    var $triggerBtn = $('#cubixsol-chat-trigger-btn');
    var $popup = $('#cubixsol-chat-popup');
    var $closeBtn = $('#cubixsol-chat-close-btn');

    if ($triggerBtn.length && $popup.length) {
      $triggerBtn.on('click', function(e) {
        e.preventDefault();
        $popup.toggleClass('cubixsol-chat-hidden');
      });

      $closeBtn.on('click', function(e) {
        e.preventDefault();
        $popup.addClass('cubixsol-chat-hidden');
      });

      $(document).on('click', function(e) {
        if (!$(e.target).closest('#cubixsol-chat-widget-container').length) {
          $popup.addClass('cubixsol-chat-hidden');
        }
      });
    }

    // Analytics & Pixel Conversion Event Dispatcher
    $(document).on('click', '.cubixsol-chat-track-click', function() {
      var agent = $(this).data('agent') || 'Default';
      var product = $(this).data('product') || '';

      // GA4
      if (typeof gtag === 'function' && typeof cubixsolChatVars !== 'undefined' && cubixsolChatVars.ga4Enabled) {
        gtag('event', 'whatsapp_click', {
          'event_category': 'Cubixsol Chat',
          'event_label': product ? ('Product: ' + product) : ('Agent: ' + agent)
        });
      }

      // Meta Pixel
      if (typeof fbq === 'function' && typeof cubixsolChatVars !== 'undefined' && cubixsolChatVars.fbEnabled) {
        fbq('trackCustom', 'WhatsAppChatClick', {
          agent_name: agent,
          product_name: product
        });
      }
    });

    // Real-Time Checkout Session Capturing
    if (typeof cubixsolChatVars !== 'undefined' && cubixsolChatVars.ajaxUrl && cubixsolChatVars.captureEnabled) {
      var captureTimeout = null;
      var lastSentPayload = '';

      function getFieldValue(selectors) {
        for (var i = 0; i < selectors.length; i++) {
          var $el = $(selectors[i]);
          if ($el.length && $el.val() && $el.val().trim().length > 0) {
            return $el.val().trim();
          }
        }
        return '';
      }

      function getScrapedCartItems() {
        var items = [];
        // Classic WooCommerce checkout review order items
        $('.woocommerce-checkout-review-order-table tbody tr.cart_item, .shop_table tbody tr.cart_item').each(function() {
          var name = $(this).find('.product-name').text().replace(/×\s*\d+/, '').trim();
          var qtyMatch = $(this).find('.product-name').text().match(/×\s*(\d+)/);
          var qty = qtyMatch ? parseInt(qtyMatch[1], 10) : 1;
          var priceText = $(this).find('.product-total').text().trim();
          if (name) {
            items.push({ name: name, quantity: qty, price: priceText, category: 'Store Products' });
          }
        });

        // WooCommerce Blocks checkout review items
        if (items.length === 0) {
          $('.wc-block-components-order-summary-item').each(function() {
            var name = $(this).find('.wc-block-components-product-name').text().trim();
            var qty = 1;
            var price = $(this).find('.wc-block-components-formatted-money-amount').text().trim();
            if (name) {
              items.push({ name: name, quantity: qty, price: price, category: 'Store Products' });
            }
          });
        }
        return items;
      }

      function captureCheckoutData() {
        var phone = getFieldValue([
          '#billing_phone',
          'input[name="billing_phone"]',
          'input[name="phone"]',
          'input[type="tel"]',
          'input[autocomplete="tel"]'
        ]);

        var email = getFieldValue([
          '#billing_email',
          'input[name="billing_email"]',
          'input[name="email"]',
          'input[type="email"]',
          'input[autocomplete="email"]'
        ]);

        var firstName = getFieldValue([
          '#billing_first_name',
          'input[name="billing_first_name"]',
          'input[name="first_name"]',
          'input[autocomplete="given-name"]'
        ]);

        var lastName = getFieldValue([
          '#billing_last_name',
          'input[name="billing_last_name"]',
          'input[name="last_name"]',
          'input[autocomplete="family-name"]'
        ]);

        var fullName = (firstName + ' ' + lastName).trim();
        if (!fullName && firstName) fullName = firstName;

        if (phone.length >= 4 || (email.length >= 5 && email.indexOf('@') !== -1)) {
          var payloadKey = phone + '|' + email + '|' + fullName;
          if (payloadKey === lastSentPayload) {
            return; // Prevent duplicate rapid requests
          }
          lastSentPayload = payloadKey;

          var clientItems = getScrapedCartItems();

          $.ajax({
            url: cubixsolChatVars.ajaxUrl,
            type: 'POST',
            data: {
              action: 'cubixsol_chat_capture_checkout',
              nonce: cubixsolChatVars.nonce,
              phone: phone,
              email: email,
              name: fullName,
              cart_token: cubixsolChatVars.cartToken || '',
              client_items: JSON.stringify(clientItems)
            },
            success: function(res) {
              // Successfully saved
            }
          });
        }
      }

      var checkoutSelectors = [
        '#billing_phone',
        'input[name="billing_phone"]',
        'input[name="phone"]',
        'input[type="tel"]',
        '#billing_email',
        'input[name="billing_email"]',
        'input[name="email"]',
        'input[type="email"]',
        '#billing_first_name',
        'input[name="billing_first_name"]',
        '#billing_last_name',
        'input[name="billing_last_name"]'
      ].join(', ');

      $(document).on('input keyup change blur paste', checkoutSelectors, function() {
        clearTimeout(captureTimeout);
        captureTimeout = setTimeout(captureCheckoutData, 300);
      });
    }
  });

})(jQuery);
