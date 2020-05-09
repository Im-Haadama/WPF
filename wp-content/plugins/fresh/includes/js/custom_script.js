jQuery(document).ready(function () {

        jQuery("#card_number").bind("keypress", function (e) {
              var keyCode = e.which ? e.which : e.keyCode
              if (!(keyCode >= 48 && keyCode <= 57)) {
                return false;
              }else{
               return true;
              }
         
        });

        jQuery("#id_number").bind("keypress", function (e) {
              var keyCode = e.which ? e.which : e.keyCode
              if (!(keyCode >= 48 && keyCode <= 57)) {
                return false;
              }else{
               return true;
              }
         
        });
        jQuery('#card_number').keyup(function (e) {
            addHyphen(this);
        });

        jQuery(".pro_qty").bind("keypress", function (e) {
              var keyCode = e.which ? e.which : e.keyCode
              if (!(keyCode >= 48 && keyCode <= 57)) {
                return false;
              }else{
               return true;
              }
         
        });
});

function addHyphen (element) {
    let val = jQuery(element).val().split('-').join(''); 
    let finalVal = val.match(/.{1,4}/g).join('-'); 
    jQuery(element).val(finalVal);
}
