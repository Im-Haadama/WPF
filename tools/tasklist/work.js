/**
 * Created by agla on 30/04/19.
 */

function project_selected() {
    var project_id = "work.php?project_id=".get_value_by_name("project");

    window.location.href = "work.php?project_id=" + project_id;

}
