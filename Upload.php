    <?php
session_start();
include('header.php');
include('connection.php');

if (!isset($_SESSION['uid'])) {
    die("Session expired");
}

$uid = $_SESSION['uid'];

// fetch user
$sel = mysqli_query($con, "SELECT * FROM user_tbl WHERE id='$uid'");
$row = mysqli_fetch_assoc($sel);

$latitude  = isset($row['latitude']) ? $row['latitude'] : '';
$longitude = isset($row['longitude']) ? $row['longitude'] : '';
?>
<style>
#loader{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(255,255,255,0.95);
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    z-index:9999;
    font-family:sans-serif;
}

.spinner{
    width:60px;
    height:60px;
    border:6px solid #ddd;
    border-top:6px solid #2c5364;
    border-radius:50%;
    animation:spin 1s linear infinite;
    margin-bottom:15px;
}

@keyframes spin{
    0%{transform:rotate(0deg);}
    100%{transform:rotate(360deg);}
}
</style>
<!-- Main Content -->
<div class="container">
<div id="loader" style="display:none;">
    <div class="spinner"></div>
    <p>Analyzing Image... Please wait</p>
</div>
  <h2 class="page-title">Upload Road Data</h2>

  <form 
    class="upload-form"
    method="POST"
    enctype="multipart/form-data"
    id="roadForm"
    novalidate
    onsubmit="showLoader()"
  >

    <!-- Road Image -->
    <label class="upload-box">
      <input 
        type="file"
        name="road_image"
        id="roadImageInput"
        accept="image/*"
        capture="environment"
        hidden
        onchange="previewImage(this)"
      >

      <svg viewBox="0 0 24 24" class="upload-icon">
        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
        <polyline points="17 8 12 3 7 8"></polyline>
        <line x1="12" y1="3" x2="12" y2="15"></line>
      </svg>

      <span>Select Road Image</span>
    </label>

    <!-- Image Preview -->
    <div class="image-preview" id="imagePreview" style="display:none;">
      <img id="previewImg">
    </div>


    <!-- Manual Location -->
    <div class="form-group">
      <label>Area Name</label>
      <input type="text" name="area_name" id="area_name" placeholder="e.g., Anna Nagar">
    </div>

    <div class="form-group">
      <label>Street Name</label>
      <input type="text" name="street_name" id="street_name" placeholder="e.g., 2nd Main Road">
    </div>

    <button type="submit" name="submit" class="btn-primary">
      Upload Road Data
    </button>

  </form>

</div>

<?php
/* ==========================
   SERVER-SIDE (PHP 5 SAFE)
   ========================== */
if (isset($_POST['submit'])) {

   

    $latitude    = $_POST['latitude'];
    $longitude   = $_POST['longitude'];
    $area_name   = $_POST['area_name'];
    $street_name = $_POST['street_name'];

    $img_name = $_FILES['road_image']['name'];
    $tmp_name = $_FILES['road_image']['tmp_name'];

    $ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
    $allowed = array('jpg','jpeg','png');

    if (!in_array($ext, $allowed)) {
        echo "<script>alert('Invalid image format');</script>";
        exit;
    }

    $new_name = time() . "_" . rand(1000,9999) . "." . $ext;
    $upload_path = "uploads/" . $new_name;

    $latitude = $_REQUEST['lat'];
    $longitude = $_REQUEST['lng'];

    if (move_uploaded_file($tmp_name, $upload_path)) {

        //$python = `python test1.py`;

        $test_path = "test/test.jpg";

        copy($upload_path, $test_path);
		
		$sql = "INSERT INTO road_uploads
                (user_id, road_image, latitude, longitude, area_name, street_name)
                VALUES
                ('$uid', '$upload_path', '$latitude', '$longitude', '$area_name', '$street_name')";

        if (mysqli_query($con, $sql)) {

            $upload_id = mysqli_insert_id($con);
			
			//$python = `python test1.py`;
			//$python = `python test1.py`;
            $python1 = "C:\\Users\\Rikza\\AppData\\Local\\Programs\\Python\\Python311\\python.exe";
            $file = "C:\\xampp\\htdocs\\road_health\\api\\test1.py";
            $python=exec($python1 . " " . $file);
			
			if($python)
			{
				// Remove extra spaces/newlines
				$python = trim($python);

				// Split by comma
				$data = explode(",", $python);

				$result = $data[0];
				$confidence  = $data[1];
				
				//Determine Severity
				if($confidence >= 0.80){
					$severity = "High";
				}
				elseif($confidence >= 0.50){
					$severity = "Medium";
				}
				else{
					$severity = "Low";
				}
				
				//save result
				$insert_result = "INSERT INTO analysis_results
								  (upload_id, defect_type, severity, confidence, analyzed_at)
								  VALUES
								  ('$upload_id', '$result', '$severity', '$confidence', NOW())";

				mysqli_query($con, $insert_result);
				

				echo "<script>
					window.location='analysis_result.php?uid=$uid&upload_id=$upload_id&result=$result&score=$confidence';
				</script>";			
            }
        } else {
            echo "<script>alert('Database error');</script>";
        }

    } else {
        echo "<script>alert('Image upload failed');</script>";
    }
}
?>

<!-- JS: Image Preview -->
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        document.getElementById('previewImg').src =
            URL.createObjectURL(input.files[0]);
        document.getElementById('imagePreview').style.display = 'block';
    }
}
</script>

<script>
function showLoader(){
    document.getElementById("loader").style.display="flex";
}
</script>


<?php include('footer.php'); ?>
