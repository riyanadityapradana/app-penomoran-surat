<?php 
//Dashboard
if ($_GET['unit'] == "beranda"){
  require_once("unit/beranda.php");
}

//Pokja
else if ($_GET['unit'] == "pokja"){
  require_once("unit/pokja/pokja.php");
}
else if ($_GET['unit'] == "create_pokja"){
  require_once("unit/pokja/create.php");
}
else if ($_GET['unit'] == "update_pokja"){
  require_once("unit/pokja/update.php");
}
else if ($_GET['unit'] == "delete_pokja"){
  require_once("unit/pokja/delete.php");
}

//Pengajuan
else if ($_GET['unit'] == "pengajuan"){
  require_once("unit/pengajuan/pengajuan.php");
}
else if ($_GET['unit'] == "detail_pengajuan"){
  require_once("unit/pengajuan/detail.php");
}
else if ($_GET['unit'] == "verifikasi_pengajuan"){
  require_once("unit/pengajuan/verifikasi.php");
}
else if ($_GET['unit'] == "tolak_pengajuan"){
  require_once("unit/pengajuan/tolak.php");
}

//Pengesahan
else if ($_GET['unit'] == "pengesahan"){
  require_once("unit/pengesahan/pengesahan.php");
}
else if ($_GET['unit'] == "detail_pengesahan"){
  require_once("unit/pengesahan/detail.php");
}

//admin
else if ($_GET['unit'] == "admin"){
  require_once("unit/admin/admin.php");
}
else if ($_GET['unit'] == "create_admin"){
  require_once("unit/admin/create.php");
}
else if ($_GET['unit'] == "update_admin"){
  require_once("unit/admin/update.php");
}
else if ($_GET['unit'] == "delete_admin"){
  require_once("unit/admin/delete.php");
}

//Laporan
else if ($_GET['unit'] == "rekap"){
  require_once("unit/rekap/rekap.php");
}
?>
