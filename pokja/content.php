<?php 
//Dashboard
if ($_GET['unit'] == "beranda"){
  require_once("unit/beranda.php");
}

//Pengjuan
else if ($_GET['unit'] == "pengajuan"){
  require_once("unit/pengajuan/pengajuan.php");
}
else if ($_GET['unit'] == "create_pengajuan"){
  require_once("unit/pengajuan/create.php");
}
else if ($_GET['unit'] == "update_pengajuan"){
  require_once("unit/pengajuan/update.php");
}
else if ($_GET['unit'] == "delete_pengajuan"){
  require_once("unit/pengajuan/delete.php");
}
else if ($_GET['unit'] == "detail_pengajuan"){
  require_once("unit/pengajuan/detail.php");
}

//Pengesahan
else if ($_GET['unit'] == "pengesahan"){
  require_once("unit/pengesahan/pengesahan.php");
}
else if ($_GET['unit'] == "detail_pengesahan"){
  require_once("unit/pengesahan/detail.php");
}

//Laporan
else if ($_GET['unit'] == "rekap"){
  require_once("unit/rekap/rekap.php");
}
?>
