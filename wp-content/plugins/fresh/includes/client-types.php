<?php
/**
 * Created by PhpStorm.
 * User: agla
 * Date: 15/02/18
 * Time: 23:23
 */
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-config.php');
?>
    <script type="text/javascript" src="/wp-content/plugins/wpf_flavor/includes/core/gui/client_tools.js"></script>
    <script type="text/javascript" src="/wp-content/plugins/wpf_flavor/includes/core/data/data.js"></script>
    <script>
        function update_client_type(id) {
            var type = get_value_by_name("select_type_" + id);

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    document.getElementById('btn_save').disabled = false;
                    location.reload();
                }
            }
            var request = "<?php print Fresh::getPost(); ?>?operation=set_client_type" +
                "&id=" + id +
                "&type=" + type;

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
        function add_client_type() {
            document.getElementById('btn_save').disabled = true;

            var user_id = get_value_by_name("client_select");

            var type = get_value_by_name("select_type_new");

            xmlhttp = new XMLHttpRequest();
            xmlhttp.onreadystatechange = function () {
                // Wait to get query result
                if (xmlhttp.readyState === 4 && xmlhttp.status === 200)  // Request finished
                {
                    document.getElementById('btn_save').disabled = false;
                    location.reload();
                }
            }
            var request = "<?php print Fresh::getPosts();?>?operation=set_client_type" +
                "&id=" + user_id +
                "&type=" + type;

            xmlhttp.open("GET", request, true);
            xmlhttp.send();

        }
    </script>

<?php
//print header_text( false, true );
