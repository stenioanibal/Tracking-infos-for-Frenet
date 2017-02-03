//Return Tracking infos in JSON Object
function getTrackingInfos(order_id) {
    $.ajax({
        url: ajaxurl,
        type: 'post',
        data: {
            action: 'call_api_to_get_tracking_info_to_order_in_my_account',
            order_id: order_id
        },
        success: function (response) {
            response = $.parseJSON(response);

            switch(response.code){
                case 2:
                    console.log("Success!");
                    console.log(response.message);
                    break;

                case 0:
                    console.log("ERROR!");
                    console.log(response.message);
                    break;

                case 5:
                    console.log("Carrier has no tracking!");
                    console.log(response.message);
                    break;
            }
        },
        error: function () {
            console.log("ERROR!");
        }
    });
    return false;
}