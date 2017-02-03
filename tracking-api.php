<?php

//Defining constants to error tests
define("WITHOUT_TRACKING_INFO", 5);
define("ERROR", 0);
define("SUCCESS", 2);
define("HTTP_SUCCESS", 200);

//Principal function to get tracking infos (if this exists) (called via AJAX)
function call_api_to_get_tracking_info_to_order_in_my_account()
{
    //Define constant to token
    define("TOKEN", "4C41F836R490FR43CDRB6E5R29755754DCAC");
    //define("TOKEN", "5603DE43R0BC1R4929R8BFFR04A4A830D567"); --> (Use only PRODUCTION)

    $order_shipping_method_id = get_order_shipping_method_id();
    $tracking_code = trim(get_post_meta($_POST['order_id'], 'tracking_code_frenet', true));
    $services = get_availables_shipping_methods_to_this_token(TOKEN);

    if ($services === ERROR) {
        echo json_encode(["code" => ERROR, "message" => ""]);
    } else {
        $current_service = get_current_service_array($order_shipping_method_id, $services);
        $available_tracking_info_services = ["TNT", "Jamef", "Jadlog", "Dlog", "Correios"];
        $is_available_tracking_info = !empty(array_search($current_service['Carrier'], $available_tracking_info_services));

        if ($is_available_tracking_info) {
            $has_tracking_number = ["Jadlog", "Correios"];
            $response = mount_tracking_info(TOKEN, $order_shipping_method_id, $current_service, $has_tracking_number, $tracking_code);

            if ($response === ERROR) {
                echo json_encode(["code" => ERROR, "message" => ""]);
            } else {
                echo json_encode(["code" => SUCCESS, "message" => $response]);
            }
        } else {
            echo json_encode(["code" => WITHOUT_TRACKING_INFO, "message" => ""]);
        }
    }

    die();
}

//Set curl settings to specific token, url and curl variable
function set_curl_settings($ch, $url, $token, $is_post)
{
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HEADER, FALSE);

    if ($is_post) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "token: $token"
    ));
}

//Get shipping method id to this order
function get_order_shipping_method_id()
{
    $order = new WC_Order($_POST['order_id']);
    $shipping_items = $order->get_items('shipping');
    $order_shipping_method_id = '';

    foreach ($shipping_items as $item) {
        $order_shipping_method_id = $item['method_id'];
    }

    $order_shipping_method_id = str_replace("FRENET_", "", $order_shipping_method_id);

    return $order_shipping_method_id;
}

//Get availables shipping method to this token
function get_availables_shipping_methods_to_this_token($token)
{
    //Getting active services from this client
    $ch_services = curl_init();
    set_curl_settings($ch_services, "http://api.frenet.com.br/shipping/info", $token, false);
    $active_services = json_decode(curl_exec($ch_services), true);

    //Get response code of this request
    $httpcode = curl_getinfo($ch_services, CURLINFO_HTTP_CODE);
    curl_close($ch_services);

    //Check if code for HTTP request is successful
    if ($httpcode === HTTP_SUCCESS) {
        return $active_services['ShippingSeviceAvailableArray'];
    } else {
        return ERROR;
    }
}

//Get current services in array of available service
function get_current_service_array($order_shipping_method_id, $services)
{
    foreach ($services as $service) {
        if ($service['ServiceCode'] == $order_shipping_method_id) {
            return $service;
        }
    }

    return ERROR;
}

//Mount the tracking info structure
function mount_tracking_info($token, $order_shipping_method_id, $current_service, $has_tracking_number, $tracking_code)
{
    $ch = curl_init();
    set_curl_settings($ch, "https://private-anon-656d554550-frenetapi.apiary-proxy.com/tracking/trackinginfo", $token, true);

    //Check if this shipping method contains Tracking number or Invoice Number
    if (!empty(array_search($current_service['Carrier'], $has_tracking_number))) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{
          \"ShippingServiceCode\": \"$order_shipping_method_id\",
          \"TrackingNumber\": \"$tracking_code\", 
          \"InvoiceNumber\": \"\",
          \"InvoiceSerie\": \"\",
          \"RecipientDocument\": \"\",
          \"OrderNumber\": \"\"
        }");
    } else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, "{
          \"ShippingServiceCode\": \"$order_shipping_method_id\",
          \"TrackingNumber\": \"\", 
          \"InvoiceNumber\": \"$tracking_code\",
          \"InvoiceSerie\": \"\",
          \"RecipientDocument\": \"\",
          \"OrderNumber\": \"\"
        }");
    }

    $response = json_decode(curl_exec($ch), true);

    //Get response code of this request
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    //Check if contains tracking events and if code for HTTP request is successful
    if ($httpcode === HTTP_SUCCESS && array_key_exists('TrackingEvents', $response)) {
        $response = $response['TrackingEvents'];

        $return = "";
        foreach ($response as $event) {
            //Change format of location
            $location = ucfirst(strtolower($event['EventLocation']));
            $location = substr_replace($location, strtoupper(substr($location, -2)), -2);

            //Making the traking info structure
            $return .= "<div class='row-tracking-info'>";
            $return .= "<article class='col-tracking col-date-tracking'>
                            <p class='text-tracking-info'>" . $event['EventDateTime'] . "</p>
                        </article>";
            $return .= "<article class='col-tracking col-description-tracking'>
                            <p class='text-tracking-info'>" . $event['EventDescription'] . "</p>
                        </article>";
            $return .= "<article class='col-tracking col-location-tracking'>
                            <p class='text-tracking-info'>" . $location . "</p>
                        </article>";
            $return .= "</div>";
        }
    } else {
        $return = ERROR;
    }

    return $return;
}