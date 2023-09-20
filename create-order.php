<?php
/*
Plugin Name: Create Order
Description: Processes related to Detrack API calls
Author: Denver Madrigal
Author URI: mailto:denvermadrigal@gmail.com
Version: 1.0
License: N/A
*/

if(!defined('ABSPATH')) return;
define('DA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DA_ROLE', 'customer');

global $current_user;
add_action('init', function() {
    if(is_user_logged_in()) {
        $current_user = wp_get_current_user();
    }
});

add_action('wp_enqueue_scripts', 'da_enqueue_scripts');
function da_enqueue_scripts() {
    wp_register_style('da-css', DA_PLUGIN_URL.'style.css');
    wp_enqueue_style('da-css');

    wp_register_script('da-fa', 'https://kit.fontawesome.com/4744c9f2f4.js');
    wp_enqueue_script('da-fa');
    
    wp_register_script('da-js', DA_PLUGIN_URL.'script.js', '', '', true);
    wp_enqueue_script('da-js');
}

add_action('woocommerce_new_order', 'create_delivery_order', 111, 1);
function create_delivery_order($order_id) {
    if($order_id) {
        $the_order = wc_get_order($order_id);
        $order_date = $the_order->get_date_created();
        $order_date = substr($order_date, 0, 10);

        $order_info = [
            'first_name' => $the_order->get_billing_first_name(),
            'last_name' => $the_order->get_billing_last_name(),
            'company' => $the_order->get_billing_company(),
            'address1' => $the_order->get_billing_address_1(),
            'address2' => $the_order->get_billing_address_2(),
            'city' => $the_order->get_billing_city(),
            'state' => $the_order->get_billing_state(),
            'postcode' => $the_order->get_billing_postcode(),
            'country' => $the_order->get_billing_email(),
            'phone' => $the_order->get_billing_phone(),
            'total_amount' => $the_order->get_total(),
            'order_created' => substr($the_order->get_date_created(), 0, 10),
            'email' => $the_order->get_billing_email()
        ];

        $order_address = ($order_info['address1'] != '')?$order_info['address1']:'';
        $order_address.= ($order_info['address2'] != '')?' '.$order_info['address2']:'';
        $order_address.= ($order_info['city'] != '')?' '.$order_info['city']:'';
        $order_address.= ($order_info['state'] != '')?' '.$order_info['state']:'';
        $order_address.= ($order_info['postcode'] != '')?' '.$order_info['postcode']:'';
        $order_address.= ($order_info['country'] != '')?' '.$order_info['country']:'';

        $items = '';
        foreach($the_order->get_items() as $item_id => $item) {
            if($items != ''){ $items.= ','; }
            $items.= "
            {
                \"id\": \"".$item->get_product_id()."\",
                \"sku\": null,
                \"purchase_order_number\": \"".$order_id."\",
                \"batch_number\": null,
                \"expiry_date\": null,
                \"description\": \"".$item->get_name()."\",
                \"comments\": null,
                \"quantity\": 1,
                \"unit_of_measure\": null,
                \"checked\": false,
                \"actual_quantity\": null,
                \"inbound_quantity\": null,
                \"unload_time_estimate\": null,
                \"unload_time_actual\": null,
                \"follow_up_quantity\": null,
                \"follow_up_reason\": null,
                \"rework_quantity\": null,
                \"rework_reason\": null,
                \"reject_quantity\": 0,
                \"reject_reason\": null,
                \"weight\": null,
                \"serial_numbers\": [],
                \"photo_url\": null
            }
            ";
        }

        define('DETRACK_API_KEY', '');
        define('DETRACK_API_URL', '');

        if(defined('DETRACK_API_KEY') && defined('DETRACK_API_URL')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, DETRACK_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_POSTFIELDS, "{
                \"data\": {
                    \"id\": \"".$order_id."\",
                    \"type\": \"Delivery\",
                    \"primary_job_status\": \"\",
                    \"open_to_marketplace\": false,
                    \"marketplace_offer\": null,
                    \"do_number\": \"".$order_id."\",
                    \"attempt\": 1,
                    \"date\": \"".$order_info['order_created']."\",
                    \"start_date\": \"".$order_info['order_created']."\",
                    \"job_age\": 1,
                    \"job_release_time\": null,
                    \"job_time\": null,
                    \"time_window\": null,
                    \"job_received_date\": null,
                    \"tracking_number\": \"T0\",
                    \"order_number\": \"".$order_id."\",
                    \"job_type\": null,
                    \"job_sequence\": null,
                    \"job_fee\": null,
                    \"address_lat\": null,
                    \"address_lng\": null,
                    \"address\": \"".$order_address."\",
                    \"company_name\": \"".$order_info['company']."\",
                    \"address_1\": \"".$order_info['address1']."\",
                    \"address_2\": \"".$order_info['address2']."\",
                    \"address_3\": null,
                    \"postal_code\": \"".$order_info['postcode']."\",
                    \"city\": \"".$order_info['city']."\",
                    \"state\": \"".$order_info['state']."\",
                    \"country\": \"".$order_info['country']."\",
                    \"geocoded_lat\": null,
                    \"geocoded_lng\": null,
                    \"billing_address\": null,
                    \"deliver_to_collect_from\": \"".$order_info['first_name']."\",
                    \"last_name\": \"".$order_info['last_name']."\",
                    \"phone_number\": \"".$order_info['phone']."\",
                    \"sender_phone_number\": null,
                    \"fax_number\": \"65432179\",
                    \"instructions\": null,
                    \"assign_to\": null,
                    \"notify_email\": \"".$order_info['email']."\",
                    \"webhook_url\": null,
                    \"zone\": null,
                    \"customer\": null,
                    \"account_number\": null,
                    \"job_owner\": null,
                    \"invoice_number\": null,
                    \"invoice_amount\": null,
                    \"payment_mode\": null,
                    \"payment_amount\": null,
                    \"group_name\": null,
                    \"vendor_name\": null,
                    \"shipper_name\": null,
                    \"source\": null,
                    \"weight\": null,
                    \"parcel_width\": null,
                    \"parcel_length\": null,
                    \"parcel_height\": null,
                    \"cubic_meter\": null,
                    \"boxes\": null,
                    \"cartons\": null,
                    \"pieces\": null,
                    \"envelopes\": null,
                    \"pallets\": null,
                    \"bins\": null,
                    \"trays\": null,
                    \"bundles\": null,
                    \"rolls\": null,
                    \"number_of_shipping_labels\": null,
                    \"attachment_url\": null,
                    \"detrack_number\": \"DET2000001\",
                    \"status\": \"\",
                    \"tracking_status\": \"\",
                    \"reason\": null,
                    \"last_reason\": null,
                    \"received_by_sent_by\": null,
                    \"note\": null,
                    \"carrier\": null,
                    \"pod_lat\": \"\",
                    \"pod_lng\": \"\",
                    \"pod_address\": \"\",
                    \"address_tracked_at\": null,
                    \"arrived_lat\": null,
                    \"arrived_lng\": null,
                    \"arrived_address\": null,
                    \"arrived_at\": null,
                    \"texted_at\": null,
                    \"called_at\": null,
                    \"serial_number\": null,
                    \"signed_at\": null,
                    \"photo_1_at\": null,
                    \"photo_2_at\": null,
                    \"photo_3_at\": null,
                    \"photo_4_at\": null,
                    \"photo_5_at\": null,
                    \"signature_file_url\": null,
                    \"photo_1_file_url\": null,
                    \"photo_2_file_url\": null,
                    \"photo_3_file_url\": null,
                    \"photo_4_file_url\": null,
                    \"photo_5_file_url\": null,
                    \"actual_weight\": null,
                    \"temperature\": null,
                    \"hold_time\": null,
                    \"payment_collected\": null,
                    \"auto_reschedule\": null,
                    \"actual_crates\": null,
                    \"actual_pallets\": null,
                    \"actual_utilization\": null,
                    \"goods_service_rating\": null,
                    \"driver_rating\": null,
                    \"customer_feedback\": null,
                    \"eta_time\": null,
                    \"live_eta\": null,
                    \"depot\": null,
                    \"depot_contact\": null,
                    \"department\": null,
                    \"sales_person\": null,
                    \"identification_number\": null,
                    \"bank_prefix\": null,
                    \"run_number\": null,
                    \"pick_up_from\": null,
                    \"pick_up_time\": null,
                    \"pick_up_lat\": null,
                    \"pick_up_lng\": null,
                    \"pick_up_address\": null,
                    \"pick_up_address_1\": null,
                    \"pick_up_address_2\": null,
                    \"pick_up_address_3\": null,
                    \"pick_up_city\": null,
                    \"pick_up_state\": null,
                    \"pick_up_country\": null,
                    \"pick_up_postal_code\": null,
                    \"pick_up_zone\": null,
                    \"pick_up_assign_to\": null,
                    \"pick_up_reason\": null,
                    \"info_received_at\": \"2022-11-01T10:04:00.017Z\",
                    \"pick_up_at\": null,
                    \"scheduled_at\": null,
                    \"at_warehouse_at\": null,
                    \"out_for_delivery_at\": null,
                    \"head_to_pick_up_at\": null,
                    \"head_to_delivery_at\": null,
                    \"cancelled_at\": null,
                    \"pod_at\": null,
                    \"pick_up_failed_count\": null,
                    \"deliver_failed_count\": null,
                    \"job_price\": null,
                    \"insurance_price\": null,
                    \"insurance_coverage\": false,
                    \"total_price\": null,
                    \"payer_type\": null,
                    \"remarks\": null,
                    \"items_count\": 2,
                    \"service_type\": null,
                    \"warehouse_address\": null,
                    \"destination_time_window\": null,
                    \"door\": null,
                    \"time_zone\": null,
                    \"vehicle_type\": null,
                    \"created_at\": \"2022-11-01T10:04:00.017Z\",
                    \"items\": [
                        ".$items."
                    ]
                }
            }");

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-API-KEY: '.DETRACK_API_KEY
            ));
    
            $response = curl_exec($ch);
            curl_close($ch);
        }
    }
}