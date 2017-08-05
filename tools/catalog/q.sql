#  Unmapped items
SELECT
  id,
  supplier_id,
  product_name,
  date,
  supplier_product_code
FROM im_supplier_price_list
WHERE
  (supplier_id, product_name) NOT IN
  (SELECT
     supplier_id,
     supplier_product_name
   FROM im_supplier_mapping)
GROUP BY supplier_id, product_name

# function get_items_to_remove()
SELECT
  id,
  post_title
FROM wp_posts
WHERE post_status = 'publish'
      AND post_type = 'product'
      AND id NOT IN (SELECT product_id
                     FROM im_supplier_mapping
                     WHERE (supplier_id, supplier_product_name)
                           IN (SELECT
                                 supplier_id,
                                 product_name
                               FROM im_supplier_price_list))
