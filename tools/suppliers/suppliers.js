/**
 * Created by agla on 15/05/19.
 */

function update_document_type(id)
{
    var new_type = get_value_by_name("document_type_" + id);
    execute_url("suppliers-post.php?operation=update_type&id=" + id + "&type=" + new_type);
}