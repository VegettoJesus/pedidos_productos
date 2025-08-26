<!DOCTYPE html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link href="https://fonts.googleapis.com/css?family=Poppins:600&display=swap" rel="stylesheet">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="{{ asset('img/logoCorp.ico') }}" type="image/x-icon">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
  <link rel="stylesheet" href="{{ asset('css/login.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
	<img class="wave" src="{{ asset('img/wave.png') }}">
	<div class="container">
		<div class="img">
			<img src="{{ asset('img/bg.png') }}">
		</div>
		<div class="login-content">
			<form method="POST" action="{{ url('/login') }}">
				@csrf
				<h2 class="title">Bienvenido</h2>

				<div class="input-div one">
					<div class="i">
						<i class="fas fa-user"></i>
					</div>
					<div class="div">
						<h5>Email</h5>
						<input type="text" class="input" name="email" required>
					</div>
				</div>

				<div class="input-div pass">
					<div class="i"> 
						<i class="fas fa-lock"></i>
					</div>
					<div class="div" style="position: relative;">
						<h5>Password</h5>
						<input type="password" class="input" name="password" id="passwordInput" required>
						<span class="toggle-password" onclick="togglePassword()" style="position: absolute; right: 10px; top: 55%; transform: translateY(-50%); cursor: pointer;">
							<i class="fas fa-eye" id="eyeIcon"></i>
						</span>
					</div>
				</div>

				<a href="#">Forgot Password?</a>
				<input type="submit" class="btn" value="Login">
			</form>

        </div>
    </div>
	@if(session('login_error'))
		<script>
			Swal.fire({
				icon: 'error',
				title: 'Error de acceso',
				text: '{{ session('login_error') }}',
				confirmButtonColor: '#EC6C01'
			});
		</script>
	@endif

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="{{ asset('js/login.js') }}"></script>
</body>
</html>