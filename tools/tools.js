/**
 * Created by agla on 03/06/18.
 */


function save_excerpt(order_id) {
    var excerpt = get_value_by_name("order_excerpt");
    // alert(excerpt);

    execute_url("orders-post.php?operation=save_order_excerpt&excerpt=" + encodeURI(excerpt) + "&order_id=" + order_id);
}

