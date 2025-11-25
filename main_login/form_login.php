<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ITUTL-3 | Log in (v2)</title>
  <link rel="icon" href="../assets/img/QQ.jpg" type="image/x-icon">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="../assets/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <!-- Logo Rumah Sakit -->
  <div class="card card-outline card-primary">
    <div class="text-center" style="margin-top:20px;">
      <img src="../assets/img/rs.jpeg" alt="Logo RS" style="max-width:280px; max-height:180px; margin-bottom:10px;">
    </div>
    <div class="card-header text-center">
      <a href="#" class="h1"><b>APDA -</b> RSPI</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Sign in to start your session</p>

      <form action="form_login_action.php" method="post">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Masukkan Username" name="username" autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-user"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
        <input class="form-control" id="pswrd" placeholder="Masukkan Password" name="password" type="password" value="">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-eye" onclick="show()"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox">
              <label for="remember">
                Remember Me
              </label>
            </div>
          </div>
          <!-- /.col -->
          <div class="col-4">
            <button type="submit" class="btn btn-primary btn-block">Login</button>
          </div>
          <!-- /.col -->
        </div>
      </form>
    </div>
    <!-- /.card-body -->
  </div>
  <!-- /.card -->
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="../assets/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../assets/dist/js/adminlte.min.js"></script>

<script>
     function show(){
          var pswrd = document.getElementById('pswrd');
          var icon  = document.querySelector('.fas');
          if (pswrd.type === "password"){
               pswrd.type = "text";
               icon.style.color ="#7f2092";
          }else{
               pswrd.type = "password";
               icon.style.color = "grey";
          }
     }
</script>
</body>
</html>