@extends('layouts.auth')
@section('title', 'Iniciar sesión')
@section('content')
<h2>Qué bueno verte de nuevo.</h2><p class="muted">Ingresa a tu cuenta y organiza tu próximo lavado.</p>
@include('partials.alerts')
<form method="POST" action="{{ route('login') }}" class="stack-form">@csrf
<label>Correo electrónico<input type="email" name="email" value="{{ old('email') }}" maxlength="200" placeholder="tu.nombre@est.univalle.edu" required autocomplete="username" autofocus></label>
<label>Contraseña<input type="password" name="password" placeholder="Ingresa tu contraseña" required autocomplete="current-password"></label>
<label class="checkbox"><input type="checkbox" name="remember" value="1"> Mantener mi sesión iniciada</label>
<button class="button full">Iniciar sesión <x-icon name="arrow"/></button>
</form><p class="auth-switch">¿Es tu primera vez? <a href="{{ route('register') }}">Crea tu cuenta</a></p><div class="auth-note"><x-icon name="info"/><span>Estudiantes: utiliza tu correo @est.univalle.edu. Personal: ingresa con el correo de tu cuenta asignada.</span></div>
@endsection
