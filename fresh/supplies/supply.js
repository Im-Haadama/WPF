/**
 * Created by agla on 01/02/19.
 */


function mission_changed(supply_id) {
    var mis = document.getElementById("mis_" + supply_id);
    var mission_id = get_value(mis);
    execute_url("supplies-post.php?operation=set_mission&supply_id=" + supply_id + "&mission_id=" + mission_id);
}
