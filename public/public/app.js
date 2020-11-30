$( document ).ready(function() {
    $(".checkout-container select#address").change(function(e) {
        console.log(this.value);
        if(this.value === "0") {
            $(".checkout-container .new-address-form").show();
        } else $(".checkout-container .new-address-form").hide();
    });
});