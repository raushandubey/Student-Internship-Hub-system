<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recruiter Registration – {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 2rem;
        }
        .register-card {
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
            border-radius: 20px; padding: 2.5rem; width: 100%; max-width: 480px;
        }
        .card-header { text-align: center; margin-bottom: 2rem; }
        .card-icon {
            width: 60px; height: 60px; background: linear-gradient(135deg, #e94560, #c62a47);
            border-radius: 16px; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
        }
        .card-icon i { color: #fff; font-size: 1.6rem; }
        .card-header h1 { color: #fff; font-size: 1.6rem; font-weight: 700; }
        .card-header p { color: rgba(255,255,255,.5); font-size: .9rem; margin-top: .3rem; }

        .form-group { margin-bottom: 1.2rem; }
        label { display: block; color: rgba(255,255,255,.7); font-size: .85rem; font-weight: 500; margin-bottom: .4rem; }
        .form-control {
            width: 100%; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.15);
            border-radius: 10px; color: #fff; padding: .75rem 1rem; font-size: .95rem;
            transition: border-color .2s;
        }
        .form-control:focus { outline: none; border-color: #e94560; }
        .form-control::placeholder { color: rgba(255,255,255,.3); }
        .error-msg { color: #eb5757; font-size: .8rem; margin-top: .3rem; }

        .btn-submit {
            width: 100%; background: linear-gradient(135deg, #e94560, #c62a47);
            color: #fff; border: none; padding: .85rem; border-radius: 12px;
            font-size: 1rem; font-weight: 700; cursor: pointer; transition: all .2s;
            margin-top: .5rem;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(233,69,96,.4); }

        .divider { text-align: center; color: rgba(255,255,255,.3); font-size: .85rem; margin: 1.2rem 0; }
        .login-link { text-align: center; color: rgba(255,255,255,.6); font-size: .9rem; }
        .login-link a { color: #e94560; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="register-card">
    <div class="card-header">
        <div class="card-icon"><i class="fas fa-user-tie"></i></div>
        <h1>Recruiter Sign Up</h1>
        <p>Create your recruiter account to start posting internships</p>
    </div>

    <form method="POST" action="{{ route('recruiter.register.submit') }}">
        @csrf

        <div class="form-group">
            <label for="name">Full Name *</label>
            <input type="text" id="name" name="name" class="form-control"
                   value="{{ old('name') }}" placeholder="John Smith" required autofocus>
            @error('name')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="email">Work Email *</label>
            <input type="email" id="email" name="email" class="form-control"
                   value="{{ old('email') }}" placeholder="you@company.com" required>
            @error('email')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="organization">Organization / Company *</label>
            <input type="text" id="organization" name="organization" class="form-control"
                   value="{{ old('organization') }}" placeholder="Acme Corp" required>
            @error('organization')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" class="form-control"
                   placeholder="Min. 8 characters" required>
            @error('password')<div class="error-msg">{{ $message }}</div>@enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirm Password *</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control" placeholder="Repeat password" required>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fas fa-user-plus me-2"></i>Create Recruiter Account
        </button>
    </form>

    <div class="divider">or</div>
    <div class="login-link">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </div>
</div>
</body>
</html>
