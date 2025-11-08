<?php
	session_start();
	require_once("../config/koneksi.php");
	if (!isset($_SESSION['id_user'])) {
		header("Location: ../main_login/form_login.php");
		exit;
	}	
	if ($_SESSION['level'] != 'Admin') {
		echo "<script>alert('Akses ditolak! Anda bukan Admin.'); window.location='../main_login/form_login.php';</script>";
		exit;
	}
	if (isset($_GET['unit'])){ $unit = $_GET['unit']; }
	ob_start();

	$id 	= $_SESSION['id_user'];
	$query 	= "SELECT * FROM tb_user WHERE id_user = '$id'";
	$admin 	= mysqli_fetch_array(mysqli_query($config, $query));
	$nama 	= $admin['nama_lengkap'];
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>AKRE-RSPI 2 | Dashboard 2</title>
		<link rel="icon" href="../assets/img/QQ.jpg" type="image/png">
		<!-- Google Font: Source Sans Pro -->
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
		<!-- Ionicons -->
		<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
		<!-- Tempusdominus Bootstrap 4 -->
		<link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
		<!-- iCheck -->
		<link rel="stylesheet" href="../assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
		<!-- Font Awesome Icons -->
		<link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
		<!-- DataTables -->
		<link rel="stylesheet" href="../assets/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
		<link rel="stylesheet" href="../assets/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
		<link rel="stylesheet" href="../assets/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
		<!-- Select2 -->
		<link rel="stylesheet" href="../assets/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
		<link rel="stylesheet" href="../assets/plugins/select2/css/select2.min.css">
		<link rel="stylesheet" href="../assets/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
		<!-- toastr -->
		<link rel="stylesheet" href="../assets/plugins/toastr/toastr.css">
		<!-- Theme style -->
		<link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
		<!-- overlayScrollbars -->
		<link rel="stylesheet" href="../assets/plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
		<!-- Daterange picker -->
		<link rel="stylesheet" href="../assets/plugins/daterangepicker/daterangepicker.css">
		<!-- summernote -->
		<link rel="stylesheet" href="../assets/plugins/summernote/summernote-bs4.min.css">
	</head>
	<body class="hold-transition sidebar-mini layout-fixed">
		<div class="wrapper">
			<!-- Preloader -->
			<div class="preloader flex-column justify-content-center align-items-center">
				<img class="animation__wobble" src="../assets/dist/img/AdminLTELogo.png" alt="AdminLTELogo" height="60" width="60">
			</div>
			<!-- Navbar -->
			<nav class="main-header navbar navbar-expand navbar-white" style="background:#000000;">
				<!-- Left navbar links -->
				<ul class="navbar-nav">
					<li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a></li>
					<li class="nav-item d-none d-sm-inline-block"><a href="main_admin.php?unit=beranda" class="nav-link">Home</a></li>
				</ul>
				<!-- Right navbar links -->
				<ul class="navbar-nav ml-auto">
				  <!-- Navbar Search -->
					<li class="nav-item">
						<a class="nav-link" data-widget="navbar-search" href="#" role="button"><i class="fas fa-search"></i></a>
						<div class="navbar-search-block">
							<form class="form-inline">
								<div class="input-group input-group-sm">
									<input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
									<div class="input-group-append">
										<button class="btn btn-navbar" type="submit"><i class="fas fa-search"></i></button>
										<button class="btn btn-navbar" type="button" data-widget="navbar-search"><i class="fas fa-times"></i></button>
									</div>
								</div>
							</form>
						</div>
					</li>
					<li class="nav-item dropdown">
						<a class="nav-link" data-toggle="dropdown" href="#">
							<i class="far fa-user"></i>
							<span class="badge badge-warning navbar-badge"></span>
						</a>

						<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right shadow-sm">
							<!-- Tambah Admin -->
							<a href="main_admin.php?unit=admin" class="dropdown-item text-primary">
								<i class="fas fa-user-plus mr-2"></i> Data Admin
							</a>

							<div class="dropdown-divider"></div>

							<!-- Logout -->
							<a href="" class="dropdown-item text-danger" data-toggle="modal" data-target="#modallogout">
								<i class="fas fa-sign-out-alt mr-2"></i> Logout
							</a>
						</div>
					</li>
				</ul>
			</nav>
			<!-- /.navbar -->

			<!-- Main Sidebar Container -->
			<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background:rgb(217, 221, 224, 1)">
				<!-- Brand Logo -->
				<a href="main_admin.php?unit=beranda" class="brand-link" style="background:rgb(0, 0, 0, 1)">
					<img src="../assets/img/QQ.jpg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
					<span class="brand-text font-weight-light">AKRED - RSPI</span>
				</a>
				<!-- Sidebar -->
				<div class="sidebar">
				  <!-- Sidebar user panel (optional) -->
					<div class="user-panel mt-3 pb-3 mb-3 d-flex">
						<div class="image">
							<img src="../assets/dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image" style="width: 47px; height: 52px; object-fit: cover;">
						</div>
					
						<div class="info ml-2">
							<a href="#" class="d-block" style="color: black; font-size: 12px; color: black; white-space: normal; word-break: break-word; line-height: 1.2;"><?php echo htmlspecialchars($nama); ?></a>
							<span class="text-muted small">ADMIN SEKRETARIAT</span>
						</div>
					</div>
					<!-- SidebarSearch Form -->
					<div class="form-inline">
						<div class="input-group" data-widget="sidebar-search">
							<input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search" style="background:rgb(255, 255, 255)">
							<div class="input-group-append">
								<button class="btn btn-sidebar"><i class="fas fa-search fa-fw"></i></button>
							</div>
						</div>
					</div>
					<!-- Sidebar Menu -->
					<nav class="mt-2">
						<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
						  <!-- Add icons to the links using the .nav-icon class with font-awesome or any other icon font library -->
							<li class="nav-item menu-open">
								<a href="main_admin.php?unit=beranda" class="nav-link active">
									<i class="nav-icon fas fa-tachometer-alt" style="color: black;"></i><p style="color: black;">Dashboard</p>
								</a>
							</li>
							<li class="nav-item menu-open">
								<a href="main_admin.php?unit=pokja" class="nav-link" style="background:rgb(231, 234, 232)">
									<i class="nav-icon fas fa-hospital-user" style="color: black;"></i><p style="color: black;">User Pokja</p>
								</a>
							</li>
							<li class="nav-item menu-open">
								<a href="main_admin.php?unit=pengajuan" class="nav-link" style="background:rgb(231, 234, 232)">
									<i class="nav-icon fas fa-download" style="color: black;"></i><p style="color: black; font-size: 13px;">Verifikasi Pengajuan Dokumen</p>
								</a>
							</li>
							<li class="nav-item menu-open">
								<a href="main_admin.php?unit=pengesahan" class="nav-link" style="background:rgb(231, 234, 232)">
									<i class="nav-icon fas fa-check" style="color: black;"></i><p style="color: black;">Dokumen Sah</p>
								</a>
							</li>
							<li class="nav-item menu-open">
								<a href="main_admin.php?unit=rekap" class="nav-link" style="background:rgb(231, 234, 232)">
									<i class="nav-icon fas fa-book" style="color: black;"></i><p style="color: black;">Rekap Dokumen</p>
								</a>
							</li>
							<!--<li class="nav-item">
								<a href="#" class="nav-link">
									<i class="nav-icon fas fa-folder" style="color: black;"></i>
									<p style="color: black;">Master Data <i class="right fas fa-angle-left" style="color: black;"></i></p>
								</a>
								<ul class="nav nav-treeview">
									<li class="nav-item">
										<a href="main_admin.php?unit=lembur" class="nav-link">
											<i class="nav-icon fas fa-hospital-user" style="color: black;"></i>
											<p style="font-size: 14px; color: black;">Data Lembur</p>
										</a>
										<a href="main_admin.php?unit=barang" class="nav-link">
											<i class="nav-icon fas fa-box-open" style="color: black;"></i>
											<p style="font-size: 14px; color: black;">Data Barang</p>
										</a>
									</li>
								</ul>
							</li>-->
						</ul>
					</nav>
					<!-- /.sidebar-menu -->
				</div>
			</aside>

			<div class="content-wrapper">
				<?php require_once ("content.php");?>
			</div>

			<!--Modal logout -->
			<div id="modallogout" class="modal fade" role="dialog">
				<div class="modal-dialog" align="center">
					<div class="modal-content">
						<div class="modal-body">
							<form method="POST" action="logout.php">
								<strong>Anda yakin ingin Logout Dari Aplikasi ?&nbsp;&nbsp;</strong>
								<input type="submit" name="logout" class="btn btn-danger" style="width: 60px" value="Ya">
								<button type="button" class="btn btn-warning" data-dismiss="modal" style="width: 60px">Batal</button>
							</form>
						</div>
					</div>
				</div>
			</div>
			<!-- Akhir Modal logout -->

		<footer class="main-footer" style="position:fixed;bottom:0;width:100%;background:#d9dde0;color:#00070c;z-index:9999;padding:0;">
			<div style="overflow:hidden;white-space:nowrap;">
				<marquee behavior="scroll" direction="left" scrollamount="6" style="font-size:16px;padding:8px 0;">
				&copy; <?= date('Y') ?> IT-RSPI | Aplikasi Penomoran Surat Akreditasi RSPI. Dikembangkan dengan ❤️ oleh Tim IT-RSPI. Seluruh hak cipta dilindungi undang-undang.
				</marquee>
			</div>
		</footer>
		</div>

		<!-- Toastr Success Message -->
		<?php if (isset($_GET['msg'])): ?>
		<script>
			toastr.success("<?= addslashes($_GET['msg']) ?>", "Sukses", {positionClass: "toast-top-right"});
		</script>
		<?php endif; ?>
		<!-- jQuery -->
		<script src="../assets/plugins/jquery/jquery.min.js"></script>
		<!-- jQuery UI 1.11.4 -->
		<script src="../assets/plugins/jquery-ui/jquery-ui.min.js"></script>
		<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
		<script>
			$.widget.bridge('uibutton', $.ui.button)
		</script>
		<!-- Bootstrap 4 -->
		<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
		<!-- Select2 -->
		<script src="../assets/plugins/select2/js/select2.full.min.js"></script>
		<!-- Bootstrap4 Duallistbox -->
		<script src="../assets/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
		<!-- InputMask -->
		<script src="../assets/plugins/moment/moment.min.js"></script>
		<script src="../assets/plugins/inputmask/jquery.inputmask.min.js"></script>
		<!-- date-range-picker -->
		<script src="../assets/plugins/daterangepicker/daterangepicker.js"></script>
		<!-- bootstrap color picker -->
		<script src="../assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
		<!-- DataTables  & Plugins -->
		<script src="../assets/plugins/datatables/jquery.dataTables.min.js"></script>
		<script src="../assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
		<script src="../assets/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
		<script src="../assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
		<script src="../assets/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
		<script src="../assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
		<script src="../assets/plugins/jszip/jszip.min.js"></script>
		<script src="../assets/plugins/pdfmake/pdfmake.min.js"></script>
		<script src="../assets/plugins/pdfmake/vfs_fonts.js"></script>
		<script src="../assets/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
		<script src="../assets/plugins/datatables-buttons/js/buttons.print.min.js"></script>
		<script src="../assets/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
		<!-- Bootstrap Switch -->
		<script src="../assets/plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
		<!-- BS-Stepper -->
		<script src="../assets/plugins/bs-stepper/js/bs-stepper.min.js"></script>
		<!-- dropzonejs -->
		<script src="../assets/plugins/dropzone/min/dropzone.min.js"></script>
		<script src="../assets/plugins/toastr/toastr.min.js"></script>
		<!-- AdminLTE App -->
		<script src="../assets/dist/js/adminlte.js"></script>
		<!-- Summernote -->
		<script src="../assets/plugins/summernote/summernote-bs4.min.js"></script>
		<!-- overlayScrollbars -->
		<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>

		<script>
			$(function () {
				//Initialize Select2 Elements
				$('.select2').select2()
				//Initialize Select2 Elements
				$('.select2bs4').select2({
					theme: 'bootstrap4'
				})
				//Datemask dd/mm/yyyy
				$('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
				//Datemask2 mm/dd/yyyy
				$('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
				//Money Euro
				$('[data-mask]').inputmask()
				//Date picker
				$('#reservationdate').datetimepicker({
					format: 'L'
				});
				//Date and time picker
				$('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });
				//Date range picker
				$('#reservation').daterangepicker()
				//Date range picker with time picker
				$('#reservationtime').daterangepicker({
					timePicker: true,
					timePickerIncrement: 30,
					locale: {
						format: 'MM/DD/YYYY hh:mm A'
					}
				})
				//Date range as a button
				$('#daterange-btn').daterangepicker(
					{
						ranges   : {
							'Today'       : [moment(), moment()],
							'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
							'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
							'Last 30 Days': [moment().subtract(29, 'days'), moment()],
							'This Month'  : [moment().startOf('month'), moment().endOf('month')],
							'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
						},
						startDate: moment().subtract(29, 'days'),
						endDate  : moment()
					},
					function (start, end) {
						$('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
					}
				)
				//Timepicker
				$('#timepicker').datetimepicker({
					format: 'LT'
				})
				//Bootstrap Duallistbox
				$('.duallistbox').bootstrapDualListbox()
				//Colorpicker
				$('.my-colorpicker1').colorpicker()
				//color picker with addon
				$('.my-colorpicker2').colorpicker()
				$('.my-colorpicker2').on('colorpickerChange', function(event) {
					$('.my-colorpicker2 .fa-square').css('color', event.color.toString());
				})
				$("input[data-bootstrap-switch]").each(function(){
					$(this).bootstrapSwitch('state', $(this).prop('checked'));
				})

			})
			// BS-Stepper Init
			document.addEventListener('DOMContentLoaded', function () {
				window.stepper = new Stepper(document.querySelector('.bs-stepper'))
			})
			// DropzoneJS Demo Code Start
			Dropzone.autoDiscover = false
			// Get the template HTML and remove it from the doumenthe template HTML and remove it from the doument
			var previewNode = document.querySelector("#template")
			previewNode.id = ""
			var previewTemplate = previewNode.parentNode.innerHTML
			previewNode.parentNode.removeChild(previewNode)
			
			var myDropzone = new Dropzone(document.body, { // Make the whole body a dropzone
				url: "/target-url", // Set the url
				thumbnailWidth: 80,
				thumbnailHeight: 80,
				parallelUploads: 20,
				previewTemplate: previewTemplate,
				autoQueue: false, // Make sure the files aren't queued until manually added
				previewsContainer: "#previews", // Define the container to display the previews
				clickable: ".fileinput-button" // Define the element that should be used as click trigger to select files.
			})

			myDropzone.on("addedfile", function(file) {
				// Hookup the start button
				file.previewElement.querySelector(".start").onclick = function() { myDropzone.enqueueFile(file) }
			})

			// Update the total progress bar
			myDropzone.on("totaluploadprogress", function(progress) {
				document.querySelector("#total-progress .progress-bar").style.width = progress + "%"
			})

			myDropzone.on("sending", function(file) {
				// Show the total progress bar when upload starts
				document.querySelector("#total-progress").style.opacity = "1"
				// And disable the start button
				file.previewElement.querySelector(".start").setAttribute("disabled", "disabled")
			})

			// Hide the total progress bar when nothing's uploading anymore
			myDropzone.on("queuecomplete", function(progress) {
				document.querySelector("#total-progress").style.opacity = "0"
			})

			// Setup the buttons for all transfers
			// The "add files" button doesn't need to be setup because the config
			// `clickable` has already been specified.
			document.querySelector("#actions .start").onclick = function() {
				myDropzone.enqueueFiles(myDropzone.getFilesWithStatus(Dropzone.ADDED))
			}
			document.querySelector("#actions .cancel").onclick = function() {
				myDropzone.removeAllFiles(true)
			}
			// DropzoneJS Demo Code End
		</script>
		<script>
			$(function () {
				$("#example1").DataTable({
					"responsive": true, "lengthChange": false, "autoWidth": false,
					"buttons": ["excel", "pdf", "print"]
				}).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
				$('#example2').DataTable({
					"paging": true,
					"lengthChange": true,
					"searching": true,
					"ordering": true,
					"info": true,
					"autoWidth": true,
					"responsive": true,
				});
				// Toastr notification
				<?php if(isset($_GET['msg'])): ?>
					toastr.options = {"positionClass": "toast-top-right", "timeOut": "3000"};
					toastr.success("<?= htmlspecialchars($_GET['msg']) ?>");
				<?php endif; ?>
				<?php if(isset($_GET['err'])): ?>
					toastr.options = {"positionClass": "toast-top-right", "timeOut": "3000"};
					toastr.error("<?= htmlspecialchars($_GET['err']) ?>");
				<?php endif; ?>
			});
		</script>
		<!-- jQuery Mapael -->
		<script src="../assets/plugins/jquery-mousewheel/jquery.mousewheel.js"></script>
		<script src="../assets/plugins/raphael/raphael.min.js"></script>
		<script src="../assets/plugins/jquery-mapael/jquery.mapael.min.js"></script>
		<script src="../assets/plugins/jquery-mapael/maps/usa_states.min.js"></script>
		<!-- ChartJS -->
		<script src="../assets/plugins/chart.js/chart2.js"></script>
	</body>
</html>