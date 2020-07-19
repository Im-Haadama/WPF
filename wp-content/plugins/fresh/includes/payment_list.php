<?php
ob_start();
ob_get_clean(); 
error_reporting(0);
global $wpdb;
$table_name = "im_payment_info";

$chk_delete = @$_POST['chk_delete'];

if (!empty($chk_delete)) {
    $count    = count($chk_delete);
    for ($i = 0; $i < $count; $i++) {
        $del_id = $chk_delete[$i];
        credit_card_remove($del_id);
//        $sql    = "DELETE FROM $table_name WHERE id =" . $del_id;
    }
}

function credit_card_remove($del_id)
{
	global $wpdb;
	MyLog($del_id, __FUNCTION__);
	$table_name = "im_payment_info";
	$card_four_digit   = $wpdb->get_var("SELECT card_four_digit FROM $table_name WHERE id = ".$del_id." ");
	$dig4 = setCreditCard($card_four_digit);
	$sql = $wpdb->query($wpdb->prepare("UPDATE $table_name SET card_number =  '".$dig4."' WHERE id = ".$del_id." "));
	return $wpdb->get_results($sql);
}

if(isset($_REQUEST['method']) && $_REQUEST['method'] == "delete_rec"){
  $del_id = $_REQUEST['id'];
  // Orig:
//  $card_four_digit   = $wpdb->get_var("SELECT card_four_digit FROM $table_name WHERE id = ".$del_id." ");
//  $sql = $wpdb->query($wpdb->prepare("UPDATE $table_name SET card_number =  '".$card_four_digit."' WHERE id = ".$del_id." "));

    // Now real delete row:
    $sql = "delete from $table_name where id = $del_id";
    MyLog($sql);
	$sql = $wpdb->query($wpdb->prepare($sql));
    $wpdb->get_results($sql);
}

function clear_duplicates()
{
	$emails = SqlQueryArrayScalar("SELECT email, count(*) FROM `im_payment_info` group by email having count(*) > 1");
	foreach ($emails as $email)
    {
        $last = SqlQuerySingleScalar("select max(id) from im_payment_info where email = '$email'");
        $sql = "delete from im_payment_info where email = '$email' and id < $last";
	    MyLog(__FUNCTION__ . ": Removing dup for $email");
        SqlQuery($sql);
    }
}

function find_user_id()
{
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	$result = SqlQuery("SELECT id, email FROM `im_payment_info`");
	while ($row = SqlFetchAssoc($result))
	{
	    $email = trim($row['email']);
	    $id = $row['id'];
		$user_id = SqlQuerySingleScalar("SELECT id from wp_users where user_email = '$email'");
		if ($user_id) {
			$sql = "update im_payment_info set user_id = $user_id where id = $id";
			SqlQuery($sql);
		}
		else
		    print "No user found for $email<br/>";
//		print $sql;

//		$user_id = SqlQuerySingleScalar("select id from im_payment_info where email = '$email'");
//		$sql = "delete from im_payment_info where email = '$email' and id < $last";
//		SqlQuery($sql);
//        print $sql . "<br/>";
	}
}

function clear_card_info()
{
    $output = Core_Html::GuiHeader(2, "Live card numbers");
	$sql = "select id, user_id, card_number from im_payment_info where card_number not like '%X%' and length(card_number) > 2";
	$result = SqlQuery($sql);
	while ($row = SqlFetchAssoc($result))
    {
        $id = $row['id'];
        $user_id = $row['user_id'];
        $token = get_user_meta($user_id, 'credit_token', true);
        $card_number = $row['card_number'];
//        $card_number = $row['card_number'];
//        $output .= "$id $user_id" . ($token? "Has token": "No Token") . "<br/>";
        if ($token) {
	        credit_card_remove($id);
//	        $output .= "Cleaning data for user $user_id<br/>";
        }
    }
//    print $output;
}
MyLog("Cleaning card info");
clear_duplicates();
//find_user_id();
clear_card_info();

$pay_result   = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id  desc");
$result = $wpdb->num_rows;
?>

<script language="javascript" type="text/javascript">
  function _multipledelete()  {   
    var f=0;      
    var len=document.frm['chk_delete[]'];        
    for (i = 0; i < len.length; i++)  {        
      if (len[i].checked) {           
        f=1;            
        if(confirm("Do you want to delete the selected record(s)?"))
        {                 
          document.frm.action="admin.php?page=payment_list";
          document.frm.submit();
          return true;              
        }             
        else{ return false;}
        break;          
      }         
      else{             
        f=0;                      
      }       
    }       
    if(f==0){         
      alert("Please select atleast one checkbox.");
      bool=false;         
      return false;        
    } 
  }   
</script> 

<div class="wrap">
<div class="tool-box">

<style>
.dashicons { color: #555; }
</style>
<h1>Payment List</h1>
      
      <form name="frm" method="post" id="formdata" onsubmit="return _multipledelete();" >

         <table width="100%" style="padding: 0 !important;	margin: 0 0 20px;box-shadow: none;	border: none !important;">
            <tr>
               <td style="	padding: 0;	box-shadow: none;background:#fff !important	;border: none;" align="left"><input class="button-primary"  name="multidelete" type="submit" id="multidelete" value="Delete"></td>
            </tr>
         </table>
		
         <table width="100%" class="widefat" id="stable">
		    
            <thead>
               <tr>
                  <th align="left"  class="del_order"><input id="" type="checkbox" name="delall" onClick="selall();" style="margin: 3px 0px 0px;"></th>
        				  <th align="left" data-orderable="true"><strong>No</strong></th>
                          <th align="left" data-orderable="true"><strong>User id</strong></th>
        				  <th align="left" data-orderable="true"><strong>Full Name</strong></th>
                  <th align="left" data-orderable="true"><strong>Email</strong></th>
                  <th align="left" data-orderable="true"><strong>Card Number</strong></th>
                  <th align="left" data-orderable="true"><strong>ID NO</strong></th>
                  <th align="left" data-orderable="false"><strong>Card Type</strong></th>
                  <th align="left" data-orderable="true"><strong>Expiry Date</strong></th>
                   <th align="left" data-orderable="false"><strong>Has Token</strong></th>
                  <th align="left" data-orderable="false"><strong>Action</strong></th>

              </tr>
            </thead>
			
            <tbody>

       <?php
        if ($result > 0) {
            foreach ($pay_result as $data) {
                $id = $data->id;
                $user_id = $data->user_id;
              $fullname = $data->full_name;
              $email = $data->email;
              $card_number = $data->card_number;
              $card_4_digit_no = $data->card_four_digit;
              $card_type = $data->card_type; 
              $exp_date_month = $data->exp_date_month;
              $exp_date_month = str_pad($exp_date_month, 2, '0', STR_PAD_LEFT);
              $exp_date_year = $data->exp_date_year;
              $id_number = $data->id_number;
              $has_token = get_user_meta($user_id, 'credit_token', true) ? 'T' : '';

        ?>             
               <tr class="alternate">
                  <td align="left">
                     <input name="chk_delete[]" id="chk_delete[]" type="checkbox" value="<?php echo (stripslashes($data->id));?>" />
                  </td>
          				<td align="left"><?php echo $id;?></td>
                   <td align="left"><?php echo $user_id;?></td>
                  <td align="left"><?php echo $fullname;?></td>
                  <td align="left"><?php echo $email;?></td>
                  <td align="left"><?php echo $card_number;?></td>
                  <td align="left"><?php echo $id_number;?></td>
                  <td align="left"><?php echo $card_type;?></td>
                  <td align="left"><?php echo $exp_date_month.'/'.$exp_date_year;?></td>
                   <td align="left"><?php echo $has_token;?></td>

                  <td align="left">
                   
                    <a title="Delete" class="ual-delete-log" href="javascript: void(0)" onclick="delete_tracking(<?php echo $data->id; ?>)"> 
                      <span class="dashicons dashicons-trash"></span>
                    </a>
                  </td>       
				   				
               </tr>
               <?php
            }
			 
        }else{
    		  ?>
    			    <tr align="center"><td colspan="9" style="text-align: center;">No data found.</td></tr>
    		  <?php
    		}
?>        
            </tbody>
         </table>
		
     
    
</div>
</div>


<style> .new-tab td {    border: 1px solid rgb(240, 243, 244);    padding: 5px 10px;    vertical-align: middle;} </style>

<script type="text/javascript" language="javascript" class="init">

jQuery(document).ready(function() {
  jQuery('#stable').DataTable();
});

function delete_tracking(id){
  if (confirm("Are you sure, you want to delete this payment information ?") == true) {
    jQuery.ajax({
      type: "POST",
      cache: false,     
      url: 'admin.php?page=payment_list',
      data: {'method':'delete_rec','id':id},
      success: function (data) {
          location.reload();      
      } 
    });
  }       
}

function selall(){
  if(document.frm.delall.checked==true){
    var len=document.frm.length;
    for(i=1;i<len;i++){
      if (document.frm.elements[i].type == "checkbox"){
        document.frm.elements[i].checked=true;
      } 
    }
  }
  else if(document.frm.delall.checked==false){
    var len=document.frm.length;
    for(i=1;i<len;i++){
      document.frm.elements[i].checked=false;
    }
  }
}
</script>