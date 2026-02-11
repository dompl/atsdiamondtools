<?php
/**
 * Use this file for all your template filters and actions.
 * Requires WooCommerce PDF Invoices & Packing Slips 1.4.13 or higher
 */
if ( ! defined('ABSPATH')) {
  exit;
}
// Exit if accessed directly
function array_swap_assoc($key1, $key2, $array) {
  $newArray = array();
  foreach ($array as $key => $value) {
    if ($key == $key1) {
      $newArray[$key2] = $array[$key2];
    } elseif ($key == $key2) {
      $newArray[$key1] = $array[$key1];
    } else {
      $newArray[$key] = $value;
    }
  }
  return $newArray;
}

add_filter('wpo_wcpdf_woocommerce_totals', 'wpo_wcpdf_woocommerce_totals_custom', 10, 3);

function wpo_wcpdf_woocommerce_totals_custom($totals, $order, $document_type) {

  /* Disable entire function with admin (acf)  */
  if (get_field('enable_new_subtotal_calculation', 'option') != true) {
    return $totals;
  }

  $position = get_option('woocommerce_currency_pos');
  if ($position == 'left') {
    $currency_left  = get_woocommerce_currency_symbol();
    $currency_right = '';
  } elseif ($position == 'right') {
    $currency_left  = '';
    $currency_right = get_woocommerce_currency_symbol();
  } elseif ($position == 'left_space') {
    $currency_left  = get_woocommerce_currency_symbol() . ' ';
    $currency_right = '';
  } elseif ($position == 'right_space') {
    $currency_left  = '';
    $currency_right = ' ' . get_woocommerce_currency_symbol();
  }

  /* Add shopping total to new subtotal */
  $order_total = $order->get_subtotal() + $order->get_shipping_total();

  /* Set new subtotal */
  $totals['cart_subtotal'] = array(
    'label' => __('Subtotal', 'wpo_wcpdf'),
    'value' => $currency_left . sprintf('%0.2f', $order_total) . $currency_right,
  );

  /* Replace subtotal with new subtotal */
  foreach ($totals as $key => $value) {
    $totals[$key]['subtotals'] = $totals['cart_subtotal'];
  }

  /* Move shoppint to the top of the array */
  $totals = array_merge(array('shipping' => $totals['shipping']), $totals);

  return $totals;

}