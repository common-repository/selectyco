jQuery(document).ready(function($)
{
    jQuery( '.sycDatePicker' ).datepicker({
      dateFormat : 'yy-mm-dd'
    });
    
    clearMessages();
       
    function clearMessages() {
      $('#sycItemStatus').hide();
    }
  
    /**
     * Process Ajax response
     */
    function itemResponse( response ) {
      if( !response.success )
      {
        if(response.data.sycReqType == "updateItem") {
          $('#sycItemStatus2').removeClass("sycSuccess").addClass("sycError");
          $('#sycItemStatus2').html(response.data.error).show();
        }
        else if(response.data.sycReqType == "deactivateItem") {
          $("#sycHr").hide();
          $('#sycItemStatus2').removeClass("sycSuccess").addClass("sycError");
          $('#sycItemStatus2').html(response.data.error).show();
          $('#sycItemStatus2').css("height", "27px");
        }
        else if(response.data.sycReqType == "send_licenceRequest") {
          $('#licenceStatus').removeClass("sycSuccess").addClass("sycError");
          $('#licenceStatus').html(response.data.error).show();
        }
        if(response.data.sycReqType == "apiGetItems") {
          $('#apiGetItemsList').removeClass("sycSuccess").addClass("sycError");
          $('#apiGetItemsList').html(response.data.error).show();
        }
        else {
          $('#sycItemStatus').removeClass("sycSuccess").addClass("sycError");     
          $('#sycItemStatus').html(response.data.error).show();
        }
      }
      else {
        if(response.data.sycReqType == "apiGetItems") {
          $('#apiGetItemsList').removeClass("sycError").addClass("sycSuccess");
          $('#apiGetItemsList').html(JSON.stringify(response.data.items,null,2)).show();
        }
        else if(response.data.sycReqType == "send_licenceRequest") {
          $('#licenceStatus').removeClass("sycError").addClass("sycSuccess");
          $('#licenceStatus').html(response.data.success).show();
        }
        else if(response.data.sycReqType == "insert_IntoWPTable") {
          $('#sycInsertItem').hide();
          $('#sycItemStatus').removeClass("sycError").addClass("sycSuccess");
          $('#sycItemStatus').html(response.data.success).show();
        }
        else if(response.data.sycReqType == "deactivateItem") {
          $('#TrA').hide();
          $('#TrB').hide();
          $('#TrC').hide();
          $('#TrD').hide();
          $('#TrE').hide();
          $('#sycItemStatus2').removeClass("sycError").addClass("sycSuccess");
          $('#sycItemStatus2').html(response.data.success).show();
        }
        else if(response.data.sycReqType == "updateItem") {
          $('#sycItemStatus2').removeClass("sycError").addClass("sycSuccess");
          $('#sycItemStatus2').html(response.data.success).show();
        }
      }
      return;
    }
    
    
    /**
    * Ajax button call
    */

    // Insert Item
    $('#sycInsertItem').click( function(event)
    {
      event.preventDefault();
      clearMessages();
      var data = {
         action: 'sycApiInsertItem',
         ajaxnonce: wp_ajax.ajaxnonce,
         sycItemName: $('#sycItemName').val(),
         sycItemType: $('#sycItemType').val(),
         sycTeaserLen: $('#sycTeaserLen').val(),
         sycPriceClass: $('#sycPriceClass').val(),
         sycBuyUrl: $('#sycBuyUrl').val(),
         sycValidTo: $('#sycValidTo').val(),
         sycWpPostId: $('#wpPostId').val()
      };

      $.post( wp_ajax.ajaxurl, data, itemResponse );
    }); 
    
    // Deactivate Item
    $('#sycDeactivateItem').click(function(event){
      event.preventDefault();
      var data = {
         action: 'sycApiDeactivateItem',
         ajaxnonce: wp_ajax.ajaxnonce,
         sycWpPostId: $('#wpPostId').val(),
         sycItemId: $('#wpSycItemId').val(),
         sycDeactivateConfirm: $('#sycDeactivateConfirm').is(":checked")
      };
      $.post( wp_ajax.ajaxurl, data, itemResponse );
    });
    
    
    // Update Item
    $('#sycUpdateItem').click(function(event) {
      event.preventDefault();
      var data = {
        action: 'sycApiUpdateItem',
        ajaxnonce: wp_ajax.ajaxnonce,
        sycWpPostId: $('#wpPostId').val(),
        newSycTeaserLen: $('#newSycTeaserLen').val(),
        newSycPriceClass: $('#newSycPriceClass').val(),
        newSycValidTo: $('#newSycValidTo').val(),
        sycItemId: $('#wpSycItemId').val()
      };

      $.post( wp_ajax.ajaxurl, data, itemResponse );
    });
    

    // Show ItemList
    $('#sycGetItems').click( function(event) {
      event.preventDefault();
      var data = {
         action: 'sycApiGetItems',
         ajaxnonce: wp_ajax.ajaxnonce,
         sycItemsOfYear: $('#sycItemsOfYear').val(),
      };

      $.post( wp_ajax.ajaxurl, data, itemResponse );
    });
    
    // Send LicenceRequest
    $('#sendLicenceRequest').click( function(event) {
      event.preventDefault();
      var data = {
         action: 'send_licenceRequest',
         ajaxnonce: wp_ajax.ajaxnonce,
         requestEmail: $('#sycId_eMail').val(),
         requestUrl: $('#sycId_url').val(),
         requestCPerson: $('#sycId_cPerson').val()
      };

      $.post( wp_ajax.ajaxurl, data, itemResponse );
    });
    
    $('#sycButtonPreviewSlider').change(function(){
      var newWidth = $('#sycButtonPreviewSlider').val();
      $('#sycId_buttonWidth').val(newWidth);
      $('#sycButtonPreview').width(newWidth);
    });
    
    
    $('#sycItemName').val($('#title').val());

    $("#title").keyup(function() {
      $("#sycItemName").val( this.value );
    }); 

    $('#sycItemName').focus(function(){
      postTitle = $('#title').val();
      $(this).val(postTitle);
    });
    
});