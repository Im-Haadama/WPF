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
        $sql    = "DELETE FROM $table_name WHERE id =" . $del_id;
        $card_four_digit   = $wpdb->get_var("SELECT card_four_digit FROM $table_name WHERE id = ".$del_id." ");
        $sql = $wpdb->query($wpdb->prepare("UPDATE $table_name SET card_number =  '".$card_four_digit."', cvv_number = '' WHERE id = ".$del_id." "));
        $wpdb->get_results($sql);
    }
}

if(isset($_REQUEST['method']) && $_REQUEST['method'] == "delete_rec"){
  $del_id = $_REQUEST['id'];
  $card_four_digit   = $wpdb->get_var("SELECT card_four_digit FROM $table_name WHERE id = ".$del_id." ");
  $sql = $wpdb->query($wpdb->prepare("UPDATE $table_name SET card_number =  '".$card_four_digit."', cvv_number = '' WHERE id = ".$del_id." "));
  $wpdb->get_results($sql);
}

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
        				  <th align="left" data-orderable="false"><strong>Full Name</strong></th>
                  <th align="left" data-orderable="false"><strong>Email</strong></th>
                  <th align="left" data-orderable="true"><strong>Card Number</strong></th>
                  <th align="left" data-orderable="true"><strong>ID NO</strong></th>
                  <th align="left" data-orderable="false"><strong>Card Type</strong></th>
                  <th align="left" data-orderable="false"><strong>Expiry Date</strong></th>
                  <th align="left" data-orderable="false"><strong>CVV No</strong></th>
                  <th align="left" data-orderable="false"><strong>Action</strong></th>
				  
              </tr>
            </thead>
			
            <tbody>

       <?php
        if ($result > 0) {
        
            $i = 1;
			
            foreach ($pay_result as $data) {

              $fullname = $data->full_name;
              $email = $data->email;
              $card_number = $data->card_number;
              $card_4_digit_no = $data->card_four_digit;
              $card_type = $data->card_type; 
              $exp_date_month = $data->exp_date_month;
              $exp_date_month = str_pad($exp_date_month, 2, '0', STR_PAD_LEFT);
              $exp_date_year = $data->exp_date_year;
              $cvv_number = $data->cvv_number;
              $id_number = $data->id_number;

        ?>             
               <tr class="alternate">
                  <td align="left">
                     <input name="chk_delete[]" id="chk_delete[]" type="checkbox" value="<?php echo (stripslashes($data->id));?>" />
                  </td>
          				<td align="left"><?php echo $i;?></td>
                  <td align="left"><?php echo $fullname;?></td>
                  <td align="left"><?php echo $email;?></td>
                  <td align="left"><?php echo $card_number;?></td>
                  <td align="left"><?php echo $id_number;?></td>
                  <td align="left"><?php echo $card_type;?></td>
                  <td align="left"><?php echo $exp_date_month.'/'.$exp_date_year;?></td>
                  <td align="left"><?php echo $cvv_number;?></td>
                  <td align="left">
                   
                    <a title="Delete" class="ual-delete-log" href="javascript: void(0)" onclick="delete_tracking(<?php echo $data->id; ?>)"> 
                      <span class="dashicons dashicons-trash"></span>
                    </a>
                  </td>       
				   				
               </tr>
               <?php
                $i = $i + 1;
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