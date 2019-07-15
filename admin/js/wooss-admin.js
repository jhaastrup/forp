(function($) {
  "use strict";

  /**
   * All of the code for your public-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  /* $(window).load(function () {
    $('.loader').fadeOut();
  }); */

  $(document).ready(function() {
    var select_choosed = $('select[name="wc_order_action"]').val();
    $('select[name="wc_order_action"]').change(function() {
      select_choosed = this.value;
      if (select_choosed == "sendbox_shipping_action") {
        $("button.button.wc-reload").on("click submit", function(e) {
          e.preventDefault();
          $("div#wooss_shipments_data").show();
        });
      } else {
        $("div#wooss_shipments_data").hide();
      }
    });

    var request_shipment_btn = $("button#wooss_request_shipment");
    request_shipment_btn.on("submit click", function(e) {
      e.preventDefault();
      var wooss_origin_name = $("input[name='wooss_origin_name']").val();
      var wooss_origin_phone = $("input[name='wooss_origin_phone']").val();
      var wooss_origin_email = $("input[name='wooss_origin_email']").val();
      var wooss_origin_country = $("input[name='wooss_origin_country']").val();
      var wooss_origin_street = $("input[name='wooss_origin_street']").val();
      var wooss_origin_state = $("input[name='wooss_origin_state']").val();
      var wooss_origin_city = $("input[name='wooss_origin_city']").val();

      var wooss_destination_name = $(
        "input[name = 'wooss_destination_name']"
      ).val();

      var wooss_destination_phone = $(
        "input[name = 'wooss_destination_phone']"
      ).val();
      var wooss_destination_email = $(
        "input[name = 'wooss_destination_email']"
      ).val();
      var wooss_destination_street = $(
        "input[name = 'wooss_destination_street']"
      ).val();
      var wooss_destination_country = $(
        "input[name = 'wooss_destination_country']"
      ).val();
      var wooss_destination_state = $(
        "input[name = 'wooss_destination_state']"
      ).val();
      var wooss_destination_city = $(
        "input[name = 'wooss_destination_city']"
      ).val();
      var wooss_selected_courier = $("select#wooss_selected_courier").val();
      $("select#wooss_selected_courier").change(function() {
        wooss_selected_courier = $(this).val();

        // console.log(wooss_selected_courier, "hahh");
      });
      var wooss_order_id = $("textarea#wooss_items_list").data("id");

      var data = {
        wooss_origin_name: wooss_origin_name,
        wooss_origin_phone: wooss_origin_phone,
        wooss_origin_email: wooss_origin_email,
        wooss_origin_country: wooss_origin_country,
        wooss_origin_state: wooss_origin_state,
        wooss_origin_city: wooss_origin_city,
        wooss_origin_street: wooss_origin_street,

        wooss_destination_name: wooss_destination_name,
        wooss_destination_phone: wooss_destination_phone,
        wooss_destination_email: wooss_destination_email,
        wooss_destination_country: wooss_destination_country,
        wooss_destination_state: wooss_destination_state,
        wooss_destination_street: wooss_destination_street,
        wooss_destination_city: wooss_destination_city,
        wooss_selected_courier: wooss_selected_courier,
        wooss_order_id: wooss_order_id,
        security: wooss_ajax_object.wooss_ajax_security
      };
      $.post(
        wooss_ajax_object.wooss_ajax_url,
        {
          action: "request_shipments",
          data: data
        },
        function(response) {
          if (response == 0) {
            alert("An error occured");
          } else if (response == 1) {
            window.location.reload();
          } else if (response == 2) {
            alert(
              "Unable to request shipment at this time login to your sendbox account and top up your wallet"
            );
          }
        }
      );
    });
  });
})(jQuery);
