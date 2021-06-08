<?php
   if(isset($_POST['import'])){
   if(isset($_FILES["csvFile"]["type"]))
   {
   	$file_extension = pathinfo($_FILES["csvFile"]["name"], PATHINFO_EXTENSION);
       // Validate file input to check if is not empty
       if (! file_exists($_FILES["csvFile"]["tmp_name"])) {
           $response = array(
               "type" => "error",
               "message" => "File input should not be empty."
           );
       } // Validate file input to check if is with valid extension
       else if ($file_extension != "csv") {
               $response = array(
                   "type" => "error",
                   "message" => "Invalid CSV: File must have .csv extension."
               );
           } // Validate file size
       else if (($_FILES["csvFile"]["size"] > 2000000)) {
               $response = array(
                   "type" => "error",
                   "message" => "Invalid CSV: File size is too large."
               );
           } // Validate if all the records have same number of fields
       	else
   		{
   			    $file_name=$_FILES["csvFile"]["name"];
                   $rand_file_name=rand()."_".$file_name;
   				$sourcePath = $_FILES['csvFile']['tmp_name']; // Storing source path of the file in a variable
   				$targetPath = "sampledata.csv"; // Target path where file is to be stored
   				unlink($targetPath);
   				move_uploaded_file($sourcePath,$targetPath); // Moving Uploaded file				
   		        $response = array(
                   "type" => "success",
                   "message" => "Moved."
                   );	
   		}
   	}
   print_r($response);
   }
   
   $row = 1;
   if (($handle = fopen("sampledata.csv", "r")) !== FALSE) {
       while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
           $num = count($data);
           $row++;
           $Date_format = trim($data[1]);
   	         if (!empty($Date_format)) {
                $csvDate= date('Y-m-d', strtotime($Date_format));
                }
               $csvPrice = (float) $data[3];
               if(ctype_alnum($data[2]) and abs($csvPrice)>0){            
               $csvName=strtoupper($data[2]);
               $csvArray[$csvName][$csvDate]=$csvPrice;
                }
       }
       fclose($handle);
   }
   
   function date_compare($a, $b)
   {
       $t1 = strtotime($a['date']);
       $t2 = strtotime($b['date']);
       return $t1 - $t2;
   }    
   
   $arrKeys=array_keys($csvArray);
   //print_r($arrKeys);
   if(isset($_REQUEST['compList'])){
       $compName=$_REQUEST['compList'];
       $fromDate=$_REQUEST['fromDate'];
       $toDate=$_REQUEST['toDate'];
   $companyShares=$csvArray[$compName];
   ksort($companyShares);
   //print_r($companyShares);
   $start = strtotime($fromDate);
   $end =   strtotime($toDate);
   $iv= 0;
   foreach($companyShares as $date => $money) {
       $timestamp = strtotime($date);
       if($timestamp >= $start && $timestamp <= $end) {
         // echo $date."The date $money is within our date range\n";
            $dataPoints[$iv]['y']=$money;
            $dataPoints[$iv]['label']=$date;
            $finalArr[$date]=$money;
            $iv++;             
       } 
   }
   
   if($iv==0){
     echo"<script>alert('No data present from the given range!!')</script>";
    // $finalArr=array_slice($companyShares, 0, 3, true);// Tp show atlest 3 value
   }
   }
   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <title>Joe Trading</title>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
      <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
   </head>
   <body>
      <div class="container-fluid">
         <div class="row content">
            <!-- Start of Upload CSV and side menu file-->
            <div class="col-sm-3 sidenav ">
               <h2>JOE TRADING</h2>
               <ul class="nav nav-pills nav-stacked">
                  <li class="active"><a href="#section1">Upload File</a></li>
                  <br>
                  <li>
                     <div class="custom-file mb-3">
                        <form id="add_studentcsv" action="" method="POST" enctype="multipart/form-data">
                           <pre>
      Choose CSV File to Upload
	  <input type="file"  accept=".csv" id="file" name="csvFile" required>
	  <a href="sampledata.csv" download> Download sample </a>

	 <input type="text" name="fileSubmit" hidden>
	  <input type="submit" id="submit" name="import" value="upload" style="float: left;">
	   </pre>
                        </form>
                     </div>
                  </li>
               </ul>
               <br>
            </div>
            <br>
            <!-- End of Upload CSV file-->
            <div class="col-sm-9">
               <div class="well">
                  <nav class="navbar navbar-expand-sm bg-dark navbar-dark">
                     <div class="form-inline" >
                        From date<input type="date" id="fromDate" name="fromDate" >
                        To date<input type="date" id="toDate" name="toDate" >
                        Company Name <input list="cities"  id="compList" name="compList" placeholder="Company Name" autocomplete="off" required>
                        <datalist id="cities" class="dropdown-menu">
                           <?php
                              foreach($arrKeys as $comVal){
                                 $sn=$sn+1;
                                 echo " <option value='".$comVal."'>";
                              }
                              ?>
                        </datalist>
                        <button class="btn btn-success"  onclick="loadAnalysis()">Search</button>
                     </div>
                  </nav>
               </div>
               <?php
                  if(isset($_REQUEST['fromDate'])){
                  ?>
               <div class="row">
                  <div class="col-sm-6" id="chartContainer" style = "width: 450px; height: 350px; margin: 0 auto" >
                     <div class="well">
                     </div>
                  </div>
                  <div class="col-sm-6">
                     <div class="well">
                        <div >
                           <ul class="list-group">
                              <?php
                                 $oldmode="";
                                    foreach($finalArr as $key => $val){
                                        $nextAmt=next($finalArr);
                                        if($nextAmt<$val){
                                            $mode='sell';
                                        }
                                    if($nextAmt>$val){
                                            $mode='buy';
                                        }
                                        if($mode=="buy" && $oldmode!=$mode){
                                  $buyVal=$val;
                                   echo "Buy on ".$key." Morning <br>";
                                        }
                                        elseif($mode=="sell" && $oldmode!=$mode){
                                   echo "Sell on ".$key." Evening (Trading closes)<br>";
                                   if($oldmode==""){}
                                   else{
                                   $tot=$tot+$val-$buyVal;
                                   }
                                        }
                                        $oldmode=$mode;
                                    }
                                    echo "Total Profit :".$tot;
                                    ?>			        
                           </ul>
                        </div>
                     </div>
                  </div>
               </div>
               <?php
                  }
                  ?>
            </div>
         </div>
      </div>
      </div>
      <input type='text' hidden readonly id="flag" name="flag"   value="<?php echo$flag;?>"               
   </body>
   <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
   <script language = "JavaScript">
      window.onload = function() {
      
      var chart = new CanvasJS.Chart("chartContainer", {
      animationEnabled: true,
      theme: "light2",
      title:{
      text: "Price Analysis"
      },
      axisY: {
      title: "Stock Price"
      },
      data: [{
      type: "column",
      yValueFormatString: "#,##0.## Rupees",
      dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
      }]
      });
      chart.render();
      
      }
      function loadAnalysis() {
      var fromDate = $("#fromDate").val();
      var toDate = $("#toDate").val();
      var compList = $("#compList").val();
      var flag = $("#flag").val();
      var dateReg = /^\d{4}([./-])\d{2}\1\d{2}$/;
      var obj = $("#cities").find("option[value='" + compList + "']");
      
      if (!fromDate.match(dateReg)) {
      $("#fromDate").focus();
      return false;
      }
      if (!toDate.match(dateReg)) {
      $("#toDate").focus();
      return false;
      }
      if (fromDate > toDate) {
      alert("From date should not be Greater then To date!!");
      $("#fromDate").focus();
      return false;
      
      }
      if (obj != null && obj.length > 0) {} else {
      alert("Enter Valid Company !!");
      $("#compList").focus();
      return false;
      
      }
      if (compList == "") {
      $("#compList").focus();
      return false;
      }
      var url = "index.php?fromDate=" + fromDate + "&toDate=" + toDate + "&compList=" + compList + "&flag=" + flag;
      window.open(url, '_self');
      
      }
      
   </script>
</html>
