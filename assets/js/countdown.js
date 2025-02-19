(function($) {
  'use strict';

  function updateCountdown() {
      $.ajax({
          url: shippingCountdown.ajax_url,
          type: 'POST',
          data: {
              action: 'shipping_countdown',
              nonce: shippingCountdown.nonce
          },
          success: function(response) {
              if (response.success && response.data.message) {
                  $('#shipping-countdown').html(response.data.message);
              }
          }
      });
  }

  // Initial update
  $(document).ready(function() {
      updateCountdown();
      
      // Update every minute
      setInterval(updateCountdown, 60000);
  });

})(jQuery);
