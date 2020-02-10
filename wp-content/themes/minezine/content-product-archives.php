<?php
/**
* The template for displaying content of search/archives.
* @package MineZine
* @since MineZine 1.0.0
*/
?>
<li class="product type-product post-<?php echo get_the_ID(); ?> status-publish first instock product_cat-fruits has-post-thumbnail shipping-taxable purchasable product-type-simple">  
  <?php
    $product_id = get_the_ID();
    $product = wc_get_product( $product_id );
    $currency = get_woocommerce_currency_symbol();
  ?>

  <a href="<?php echo get_permalink(); ?>" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
    <?php if( $product->is_on_sale() ) { 
    echo '<span class="onsale"><font style="vertical-align: inherit;"><font style="vertical-align: inherit;">s a l e!</font></font></span>';
    }
    ?>
    <?php if ( has_post_thumbnail() ) { 
      echo get_the_post_thumbnail( $product_id, 'thumbnail', array( 'class' => '' ) );
    } ?>
    <h2 class="woocommerce-loop-product__title"><?php the_title(); ?></h2> 
    <span class="price">
      <?php 
      if( $product->is_type( 'simple' ) ){ 
        $sale_price = $product->get_sale_price();
        $reg_price = $product->get_regular_price();
        if( $product->is_on_sale() ) {
         echo '<span class="woocommerce-Price-amount amount block_price"><span class="woocommerce-Price-currencySymbol">'.$currency.'</span>'.$reg_price.'</span> <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'.$currency.'</span>'.$sale_price.'</span>';
        }else{
          echo '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'.$currency.'</span>'.$reg_price.'</span>';
       }  
      } elseif( $product->is_type( 'variable' ) ){ 
        $min_price = $product->get_variation_price( 'min' );
        $max_price = $product->get_variation_price( 'max' );
        if($min_price != $max_price) {
          echo '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'.$currency.'</span>'.$min_price.'</span> - <span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'.$currency.'</span>'.$max_price.'</span>';
        }else{
          $price_range = esc_attr($currency).''.$min_price; 
          echo '<span class="woocommerce-Price-amount amount"><span class="woocommerce-Price-currencySymbol">'.$currency.'</span>'.$min_price.'</span>';
        }
      } ?>
  </span>
  </a>          
  <?php 
  if( $product->is_type( 'simple' ) ){  ?>

    <form class="cart" id="crt-form"  method="post" enctype="multipart/form-data">
      <input type="hidden" name="product_id" id="product_id" value="<?php $product_id; ?>">
      <input style="display: none;" type="number" step="1" min="1" name="quantity" value="1" title="Qty" class="input-text qty text">
      <button type="submit" name="add-to-cart" value="<?php echo $product_id; ?>" class="button product_type_simple add_to_cart_button ajax_add_to_cart srh_addto_cart"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
    </form> 
    <?php 
  } elseif( $product->is_type( 'variable' ) ){
    $min_price = $product->get_variation_price( 'min' );
    $max_price = $product->get_variation_price( 'max' );
    $price_range = esc_attr($currency).''.$min_price.' - '.esc_attr($currency).''.$max_price;
    ?>
    <a href="<?php echo get_permalink($product_id); ?>" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart srh_addto_cart" data-product_id="8" data-product_sku="" aria-label="Add “banana” to your cart" rel="nofollow">Select options</a>
    <?php 
  } ?>
</li>