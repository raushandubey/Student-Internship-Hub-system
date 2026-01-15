<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InternshipHub - Student Career Platform</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --light: #f8fafc;
            --dark: #0f172a;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --gradient-rainbow: linear-gradient(45deg, #667eea, #764ba2, #f093fb, #f5576c, #4facfe, #00f2fe);
            --gradient-neon: linear-gradient(45deg, #ff006e, #8338ec, #3a86ff);
            
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-neon: 0 0 20px rgba(99, 102, 241, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--gray-700);
            overflow-x: hidden;
            background: var(--gray-50);
        }

        html {
            scroll-behavior: smooth;
        }

        /* Advanced Custom Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-15px) rotate(1deg); }
            50% { transform: translateY(-30px) rotate(0deg); }
            75% { transform: translateY(-15px) rotate(-1deg); }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(100px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-100px) rotateX(45deg);
            }
            to {
                opacity: 1;
                transform: translateX(0) rotateX(0deg);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px) rotateX(45deg);
            }
            to {
                opacity: 1;
                transform: translateX(0) rotateX(0deg);
            }
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.3) rotate(-10deg);
            }
            to {
                opacity: 1;
                transform: scale(1) rotate(0deg);
            }
        }

        @keyframes morphing {
            0%, 100% { border-radius: 60% 40% 30% 70%/60% 30% 70% 40%; }
            50% { border-radius: 30% 60% 70% 40%/50% 60% 30% 60%; }
        }

        @keyframes neonPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.4), 0 0 40px rgba(99, 102, 241, 0.2); }
            50% { box-shadow: 0 0 40px rgba(99, 102, 241, 0.8), 0 0 80px rgba(99, 102, 241, 0.4); }
        }

        @keyframes particleFloat {
            0% { transform: translateY(100vh) translateX(0) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(200px) rotate(360deg); opacity: 0; }
        }

        @keyframes textGlow {
            0%, 100% { text-shadow: 0 0 10px rgba(99, 102, 241, 0.5); }
            50% { text-shadow: 0 0 20px rgba(99, 102, 241, 0.8), 0 0 30px rgba(99, 102, 241, 0.6); }
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes spin3D {
            0% { transform: rotateY(0deg) rotateX(0deg); }
            50% { transform: rotateY(180deg) rotateX(10deg); }
            100% { transform: rotateY(360deg) rotateX(0deg); }
        }

        /* Particle System */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(99, 102, 241, 0.6);
            border-radius: 50%;
            animation: particleFloat 15s linear infinite;
        }

        .particle:nth-child(odd) {
            background: rgba(16, 185, 129, 0.6);
            animation-delay: -5s;
            animation-duration: 12s;
        }

        .particle:nth-child(3n) {
            background: rgba(245, 158, 11, 0.6);
            animation-delay: -10s;
            animation-duration: 18s;
            width: 6px;
            height: 6px;
        }

        /* Loading Screen with Advanced Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #667eea, #764ba2, #f093fb);
            background-size: 400% 400%;
            animation: gradientShift 3s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            transition: all 1s ease;
        }

        .loading.hidden {
            opacity: 0;
            pointer-events: none;
            transform: scale(1.1);
        }

        .loading-content {
            text-align: center;
            color: white;
        }

        .spinner-advanced {
            width: 80px;
            height: 80px;
            border: 4px solid rgba(255, 255, 255, 0.2);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin3D 2s linear infinite;
            margin: 0 auto 20px;
        }

        .loading-text {
            font-size: 1.5rem;
            font-weight: 600;
            animation: textGlow 2s ease-in-out infinite;
        }

        .loading-progress {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin: 20px auto 0;
            overflow: hidden;
        }

        .loading-bar {
            height: 100%;
            background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0.8));
            border-radius: 2px;
            animation: loadingBar 3s ease-in-out;
        }

        @keyframes loadingBar {
            0% { width: 0%; }
            100% { width: 100%; }
        }

        /* Utilities */
        .gradient-text {
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: textGlow 3s ease-in-out infinite;
        }

        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .morphing-shape {
            animation: morphing 8s ease-in-out infinite;
        }

        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-bounce { animation: bounce 3s ease-in-out infinite; }
        .animate-pulse { animation: pulse 3s ease-in-out infinite; }
        .animate-neon { animation: neonPulse 2s ease-in-out infinite; }

        /* Navigation with Advanced Effects */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 1rem 0;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(30px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            transform: translateY(0);
        }

        .navbar-brand {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--gray-900);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
            color: var(--primary);
        }

        .navbar-brand i {
            color: var(--primary);
            font-size: 2rem;
            animation: spin3D 10s linear infinite;
        }

        .navbar-nav .nav-link {
            font-weight: 500;
            color: var(--gray-700);
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 0 0.25rem;
            position: relative;
            overflow: hidden;
        }

        .navbar-nav .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.2), transparent);
            transition: left 0.5s;
        }

        .navbar-nav .nav-link:hover::before {
            left: 100%;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.2);
        }

        /* Advanced Button Styles */
        .btn {
            font-weight: 600;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            border: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transform-origin: center;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50px;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: scale(0);
            transition: transform 0.4s ease;
        }

        .btn:hover::after {
            transform: scale(1);
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
            color: var(--white);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
            position: relative;
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.3);
        }

        .btn-lg {
            padding: 1.2rem 2.5rem;
            font-size: 1.125rem;
        }

        .btn i {
            transition: transform 0.3s ease;
        }

        .btn:hover i {
            transform: translateX(3px);
        }

        /* Hero Section with Advanced Effects */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            background: var(--gradient-primary);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
            border-radius: 50px;
            font-weight: 500;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideInLeft 1s ease;
            transition: all 0.3s ease;
        }

        .hero-badge:hover {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.25);
        }

        .hero-badge i {
            animation: spin3D 3s linear infinite;
        }

        .hero-title {
            font-size: 4.5rem;
            font-weight: 900;
            line-height: 1.1;
            color: var(--white);
            margin-bottom: 2rem;
            animation: slideInLeft 1s ease 0.2s both;
        }

        .hero-title .gradient-text {
            background: linear-gradient(45deg, #fff, #e2e8f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 3rem;
            max-width: 600px;
            animation: slideInLeft 1s ease 0.4s both;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 2rem;
            margin-bottom: 4rem;
            animation: slideInLeft 1s ease 0.6s both;
            flex-wrap: wrap;
        }

        .hero-stats {
            display: flex;
            gap: 4rem;
            animation: slideInUp 1s ease 0.8s both;
            flex-wrap: wrap;
        }

        .stat-item {
            text-align: center;
            color: var(--white);
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-item:hover {
            transform: translateY(-5px) scale(1.05);
            background: rgba(255, 255, 255, 0.2);
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 900;
            display: block;
            line-height: 1;
            margin-bottom: 0.5rem;
            animation: textGlow 3s ease-in-out infinite;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 500;
        }

        .hero-visual {
            position: relative;
            animation: slideInRight 1s ease 0.4s both;
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .floating-card {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 25px;
            padding: 2rem;
            color: var(--white);
            min-width: 220px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .floating-card:hover {
            transform: scale(1.05) rotateY(10deg);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .floating-card-1 {
            top: 10%;
            left: -10%;
            animation: float 8s ease-in-out infinite;
        }

        .floating-card-2 {
            top: 30%;
            right: -10%;
            animation: float 8s ease-in-out infinite 2s;
        }

        .floating-card-3 {
            bottom: 20%;
            left: 10%;
            animation: float 8s ease-in-out infinite 4s;
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            animation: morphing 6s ease-in-out infinite;
        }

        .bg-primary { background: var(--gradient-primary); }
        .bg-success { background: var(--gradient-success); }
        .bg-warning { background: var(--gradient-secondary); }

        /* Advanced Features Section */
        .features {
            padding: 10rem 0;
            background: linear-gradient(135deg, var(--gray-50) 0%, var(--white) 100%);
            position: relative;
        }

        .features::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 70% 20%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
        }

        .section-header {
            text-align: center;
            max-width: 900px;
            margin: 0 auto 6rem;
            position: relative;
        }

        .section-badge {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: var(--gradient-primary);
            color: var(--white);
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: animate-neon;
            box-shadow: var(--shadow-neon);
        }

        .section-title {
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--gray-900);
            margin-bottom: 2rem;
            line-height: 1.1;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--gradient-primary);
            border-radius: 2px;
        }

        .section-description {
            font-size: 1.3rem;
            color: var(--gray-600);
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 3rem;
        }

        .feature-card {
            background: var(--white);
            padding: 3.5rem 2.5rem;
            border-radius: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--gray-200);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            transform-origin: center;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.08), transparent);
            transition: left 0.8s;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 30px;
            background: linear-gradient(45deg, rgba(99, 102, 241, 0.1), transparent, rgba(16, 185, 129, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .feature-card:hover::after {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-15px) rotateX(5deg);
            box-shadow: 0 25px 80px rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: var(--white);
            margin-bottom: 2.5rem;
            position: relative;
            animation: morphing 8s ease-in-out infinite;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }

        .feature-description {
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 2rem;
            font-size: 1.05rem;
        }

        .feature-link {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            padding: 0.5rem 0;
        }

        .feature-link:hover {
            gap: 1rem;
            color: var(--primary-dark);
        }

        /* Advanced Stats Section */
        .stats {
            padding: 8rem 0;
            background: var(--gradient-primary);
            background-size: 400% 400%;
            animation: gradientShift 10s ease infinite;
            color: var(--white);
            position: relative;
        }

        .stats::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 15s ease-in-out infinite;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .stats-item {
            padding: 3rem 2rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-item::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), transparent, rgba(255, 255, 255, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stats-item:hover::before {
            opacity: 1;
        }

        .stats-item:hover {
            transform: translateY(-10px) scale(1.05);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .stats-number {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 1rem;
            display: block;
            animation: textGlow 4s ease-in-out infinite;
        }

        .stats-label {
            font-size: 1.2rem;
            opacity: 0.95;
            font-weight: 600;
        }

        /* Advanced How It Works */
        .how-it-works {
            padding: 10rem 0;
            background: linear-gradient(135deg, var(--white) 0%, var(--gray-50) 100%);
            position: relative;
        }

        .steps {
            position: relative;
            margin-top: 6rem;
        }

        .steps::before {
            content: '';
            position: absolute;
            top: 80px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
            z-index: 1;
            border-radius: 2px;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 4rem;
            position: relative;
            z-index: 2;
        }

        .step {
            text-align: center;
            background: var(--white);
            padding: 3rem 2rem;
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .step::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(99, 102, 241, 0.05), transparent, rgba(16, 185, 129, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .step:hover::before {
            opacity: 1;
        }

        .step:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 80px rgba(99, 102, 241, 0.15);
        }

        .step-number {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 900;
            margin: 0 auto 2.5rem;
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.3);
            position: relative;
            animation: morphing 10s ease-in-out infinite;
        }

        .step-number::before {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 50%;
            background: var(--gradient-primary);
            opacity: 0.3;
            animation: pulse 3s ease-in-out infinite;
        }

        .step-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }

        .step-description {
            color: var(--gray-600);
            line-height: 1.7;
            font-size: 1.05rem;
        }

        /* Advanced Testimonials */
        .testimonials {
            padding: 10rem 0;
            background: var(--gray-50);
            position: relative;
        }

        .testimonials::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 80%, rgba(99, 102, 241, 0.05) 0%, transparent 50%);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 3rem;
            margin-top: 6rem;
            position: relative;
            z-index: 2;
        }

        .testimonial {
            background: var(--white);
            padding: 3.5rem 2.5rem;
            border-radius: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.08);
            position: relative;
            transition: all 0.4s ease;
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }

        .testimonial::before {
            content: '"';
            position: absolute;
            top: -20px;
            left: 30px;
            font-size: 8rem;
            color: var(--primary);
            opacity: 0.1;
            font-weight: 900;
        }

        .testimonial::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(99, 102, 241, 0.05), transparent, rgba(16, 185, 129, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .testimonial:hover::after {
            opacity: 1;
        }

        .testimonial:hover {
            transform: translateY(-10px) rotateX(5deg);
            box-shadow: 0 25px 80px rgba(99, 102, 241, 0.15);
        }

        .testimonial-content {
            font-size: 1.2rem;
            line-height: 1.7;
            color: var(--gray-700);
            margin-bottom: 2.5rem;
            font-style: italic;
            position: relative;
            z-index: 2;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .author-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 800;
            font-size: 1.6rem;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            animation: morphing 8s ease-in-out infinite;
        }

        .author-info h6 {
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .author-info p {
            color: var(--gray-500);
            margin: 0;
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Advanced CTA Section */
        .cta {
            padding: 10rem 0;
            background: var(--gradient-primary);
            background-size: 400% 400%;
            animation: gradientShift 8s ease infinite;
            color: var(--white);
            text-align: center;
            position: relative;
        }

        .cta::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 50% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 6s ease-in-out infinite;
        }

        .cta-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        .cta-title {
            font-size: 4rem;
            font-weight: 900;
            margin-bottom: 2rem;
            line-height: 1.1;
            animation: textGlow 4s ease-in-out infinite;
        }

        .cta-description {
            font-size: 1.4rem;
            margin-bottom: 4rem;
            opacity: 0.95;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .btn-white {
            background: var(--white);
            color: var(--primary);
            font-weight: 800;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-white:hover {
            background: var(--gray-100);
            color: var(--primary-dark);
            transform: translateY(-5px) scale(1.05);
        }

        /* Advanced Footer */
        .footer {
            background: linear-gradient(135deg, var(--gray-900) 0%, var(--dark) 100%);
            color: var(--gray-300);
            padding: 6rem 0 3rem;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="10" cy="10" r="1" fill="rgba(99,102,241,0.1)"/><circle cx="90" cy="30" r="0.5" fill="rgba(16,185,129,0.1)"/><circle cx="30" cy="90" r="0.8" fill="rgba(245,158,11,0.1)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 4rem;
            margin-bottom: 4rem;
            position: relative;
            z-index: 2;
        }

        .footer-brand {
            color: var(--white);
        }

        .footer-brand .navbar-brand {
            color: var(--white);
            margin-bottom: 2rem;
        }

        .footer-description {
            color: var(--gray-400);
            line-height: 1.7;
            margin-bottom: 2.5rem;
            font-size: 1.05rem;
        }

        .social-links {
            display: flex;
            gap: 1.5rem;
        }

        .social-link {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--gray-800);
            color: var(--gray-400);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.4rem;
            transition: all 0.4s ease;
            border: 1px solid var(--gray-700);
        }

        .social-link:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.4);
        }

        .footer-section h6 {
            color: var(--white);
            font-weight: 800;
            margin-bottom: 2rem;
            font-size: 1.2rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .footer-links a:hover {
            color: var(--white);
            padding-left: 5px;
        }

        .footer-bottom {
            border-top: 1px solid var(--gray-800);
            padding-top: 3rem;
            text-align: center;
            color: var(--gray-500);
            position: relative;
            z-index: 2;
        }

        /* Advanced Scroll Animations */
        .reveal {
            opacity: 0;
            transform: translateY(100px) rotateX(45deg);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0) rotateX(0deg);
        }

        .reveal-left {
            opacity: 0;
            transform: translateX(-100px) rotateY(45deg);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal-left.active {
            opacity: 1;
            transform: translateX(0) rotateY(0deg);
        }

        .reveal-right {
            opacity: 0;
            transform: translateX(100px) rotateY(-45deg);
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal-right.active {
            opacity: 1;
            transform: translateX(0) rotateY(0deg);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.8rem;
            }

            .section-title {
                font-size: 2.5rem;
            }

            .cta-title {
                font-size: 2.5rem;
            }

            .hero-stats {
                flex-direction: column;
                gap: 2rem;
                text-align: center;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .steps::before {
                display: none;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .particles {
                display: none;
            }

            .floating-elements {
                display: none;
            }
        }

        /* Back to Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
        }

        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 15px 40px rgba(99, 102, 241, 0.4);
        }
    </style>
</head>

<body>
    <!-- Advanced Loading Screen -->
    <div class="loading" id="loading">
        <div class="loading-content">
            <div class="spinner-advanced"></div>
            <div class="loading-text">InternshipHub</div>
            <div class="loading-progress">
                <div class="loading-bar"></div>
            </div>
        </div>
    </div>

    <!-- Particle System -->
    <div class="particles" id="particles"></div>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg" id="navbar">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="bi bi-rocket-takeoff"></i>
                InternshipHub
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How it Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Reviews</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-primary ms-2" href="/login">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary ms-2" href="/register">Get Started</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <div class="hero-badge">
                            <i class="bi bi-stars"></i>
                            <span>Rule-Based Career Platform</span>
                        </div>

                        <h1 class="hero-title">
                            Find Your Perfect
                            <span class="gradient-text">Internship</span>
                            with AI Magic
                        </h1>

                        <p class="hero-subtitle">
                            Join thousands of students who discovered their dream careers through our skill-based matching system. Get personalized recommendations, real-time updates, and career guidance through our database-driven platform.
                        </p>

                        <div class="hero-buttons">
                            <a href="/register" class="btn btn-white btn-lg">
                                <i class="bi bi-rocket-takeoff"></i>
                                Start Your Journey
                            </a>
                            <a href="#how-it-works" class="btn btn-outline-primary btn-lg" style="border-color: rgba(255,255,255,0.4); color: white;">
                                <i class="bi bi-play-circle"></i>
                                Learn More
                            </a>
                        </div>

                        <div class="hero-stats">
                            <div class="stat-item">
                                <span class="stat-number">25K+</span>
                                <div class="stat-label">Students Placed</div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">1200+</span>
                                <div class="stat-label">Partner Companies</div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">98%</span>
                                <div class="stat-label">Success Rate</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="hero-visual">
                        <div class="floating-elements">
                            <div class="floating-card floating-card-1">
                                <div class="card-icon bg-primary">
                                    <i class="bi bi-code-slash"></i>
                                </div>
                                <h6>Software Development</h6>
                                <p>95% Match Score</p>
                            </div>

                            <div class="floating-card floating-card-2">
                                <div class="card-icon bg-success">
                                    <i class="bi bi-graph-up-arrow"></i>
                                </div>
                                <h6>Data Science</h6>
                                <p>89% Match Score</p>
                            </div>

                            <div class="floating-card floating-card-3">
                                <div class="card-icon bg-warning">
                                    <i class="bi bi-palette"></i>
                                </div>
                                <h6>UI/UX Design</h6>
                                <p>92% Match Score</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features reveal" id="features">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Features</div>
                <h2 class="section-title">Why Choose InternshipHub?</h2>
                <p class="section-description">
                    Advanced skill-matching technology meets personalized career guidance to transform your internship search experience into an efficient and successful journey.
                </p>
            </div>

            <div class="features-grid">
                <div class="feature-card reveal-left">
                    <div class="feature-icon bg-primary">
                        <i class="bi bi-filter"></i>
                    </div>
                    <h3 class="feature-title">Skill-Based Matching</h3>
                    <p class="feature-description">
                        Our rule-based system analyzes your skills, preferences, and career goals to find perfect internship matches with high accuracy and personalization.
                    </p>
                    <a href="#" class="feature-link">
                        Discover More <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card reveal">
                    <div class="feature-icon bg-success">
                        <i class="bi bi-lightning-charge"></i>
                    </div>
                    <h3 class="feature-title">Real-Time Alerts</h3>
                    <p class="feature-description">
                        Never miss an opportunity! Get instant notifications when new internships matching your profile are posted by top-tier companies worldwide.
                    </p>
                    <a href="#" class="feature-link">
                        Discover More <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card reveal-right">
                    <div class="feature-icon bg-warning">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="feature-title">Verified Companies</h3>
                    <p class="feature-description">
                        All our partner companies undergo rigorous verification to ensure legitimate opportunities with proper mentorship and career development programs.
                    </p>
                    <a href="#" class="feature-link">
                        Discover More <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card reveal-left">
                    <div class="feature-icon bg-primary">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <h3 class="feature-title">Career Analytics</h3>
                    <p class="feature-description">
                        Get detailed insights about your application performance, skill gaps, and personalized recommendations to accelerate your career growth.
                    </p>
                    <a href="#" class="feature-link">
                        Discover More <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card reveal">
                    <div class="feature-icon bg-success">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3 class="feature-title">Community Network</h3>
                    <p class="feature-description">
                        Connect with fellow interns, share experiences, and get mentorship from industry experts in our thriving community ecosystem.
                    </p>
                    <a href="#" class="feature-link">
                        Discover More <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="feature-card reveal-right">
                    <div class="feature-icon bg-warning">
                        <i class="bi bi-award"></i>
                    </div>
                    <h3 class="feature-title">Skill Development</h3>
                    <p class="feature-description">
                        Access premium courses, workshops, and resources to enhance your skills and increase your internship success rate significantly.
                    </p>
                    <a href="#" class="feature-link">
                        Discover More <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stats-item">
                    <span class="stats-number" data-count="25000">0</span>
                    <div class="stats-label">Students Placed</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number" data-count="1200">0</span>
                    <div class="stats-label">Partner Companies</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number" data-count="98">0</span>
                    <div class="stats-label">Success Rate %</div>
                </div>
                <div class="stats-item">
                    <span class="stats-number" data-count="50">0</span>
                    <div class="stats-label">Countries Covered</div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works reveal" id="how-it-works">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Process</div>
                <h2 class="section-title">How It Works</h2>
                <p class="section-description">
                    Start your internship journey in just three simple steps and land your dream role with our skill-matching platform
                </p>
            </div>

            <div class="steps">
                <div class="steps-grid">
                    <div class="step reveal-left">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Create Your Profile</h3>
                        <p class="step-description">
                            Build a comprehensive profile with your skills, education, interests, and career aspirations. Our AI will understand your unique strengths and preferences.
                        </p>
                    </div>

                    <div class="step reveal">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Get AI Recommendations</h3>
                        <p class="step-description">
                            Our advanced algorithms analyze thousands of opportunities and present you with personalized matches based on your profile and preferences.
                        </p>
                    </div>

                    <div class="step reveal-right">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Apply & Get Hired</h3>
                        <p class="step-description">
                            Apply to multiple internships with one click, track your progress in real-time, and get interview preparation support from our experts.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials reveal" id="testimonials">
        <div class="container">
            <div class="section-header">
                <div class="section-badge">Success Stories</div>
                <h2 class="section-title">What Our Students Say</h2>
                <p class="section-description">
                    Hear from thousands of students who transformed their careers with InternshipHub and achieved their dream internships
                </p>
            </div>

            <div class="testimonials-grid">
                <div class="testimonial reveal-left">
                    <p class="testimonial-content">
                        InternshipHub's AI matching is incredible! I got my dream internship at Google within 2 weeks of signing up. The platform understood exactly what I was looking for and connected me with the perfect opportunity that matched my skills and aspirations.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RD</div>
                        <div class="author-info">
                            <h6>Raushan Dubey</h6>
                            <p>Software Engineering Intern at Google</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial reveal">
                    <p class="testimonial-content">
                        The personalized recommendations were spot-on! I never thought I'd find an internship that perfectly matched my skills in data science and my passion for healthcare. The platform made it happen effortlessly and I couldn't be happier.
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">MK</div>
                        <div class="author-info">
                            <h6>Mangal Kumar</h6>
                            <p>Data Science Intern at Microsoft</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial reveal-right">
                    <p class="testimonial-content">
                        Amazing platform with real results! The community support and mentorship program helped me not just find an internship, but also develop skills I never knew I needed. Highly recommended for all students!
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">AR</div>
                        <div class="author-info">
                            <h6>Ankit Razzput</h6>
                            <p>UI/UX Design Intern at Adobe</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">Ready to Transform Your Career?</h2>
                <p class="cta-description">
                    Join 25,000+ students who have successfully launched their careers with our skill-matching platform. Your dream internship is just one click away!
                </p>
                <div class="cta-buttons">
                    <a href="/register" class="btn btn-white btn-lg">
                        <i class="bi bi-rocket-takeoff"></i>
                        Get Started for Free
                    </a>
                    <a href="/login" class="btn btn-outline-primary btn-lg" style="border-color: rgba(255,255,255,0.4); color: white;">
                        <i class="bi bi-person"></i>
                        Sign In
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a class="navbar-brand" href="#home">
                        <i class="bi bi-rocket-takeoff"></i>
                        InternshipHub
                    </a>
                    <p class="footer-description">
                        Empowering students worldwide to discover and secure their dream internships through revolutionary AI technology and personalized career guidance.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="#" class="social-link">
                            <i class="bi bi-facebook"></i>
                        </a>
                    </div>
                </div>

                <div class="footer-section">
                    <h6>Platform</h6>
                    <ul class="footer-links">
                        <li><a href="#">Browse Internships</a></li>
                        <li><a href="#">Companies</a></li>
                        <li><a href="#">Success Stories</a></li>
                        <li><a href="#">Career Resources</a></li>
                        <li><a href="#">Mobile App</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h6>Support</h6>
                    <ul class="footer-links">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Support</a></li>
                        <li><a href="#">Community Forum</a></li>
                        <li><a href="#">Video Tutorials</a></li>
                        <li><a href="#">FAQ</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h6>Company</h6>
                    <ul class="footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press Kit</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 InternshipHub. All rights reserved. Made with ❤️ for students worldwide.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Advanced Loading Screen
        window.addEventListener('load', function() {
            const loading = document.getElementById('loading');
            setTimeout(() => {
                loading.classList.add('hidden');
            }, 3500);
        });

        // Create Particle System
        function createParticles() {
            const particles = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 15 + 's';
                particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
                particles.appendChild(particle);
            }
        }

        // Advanced Navbar Scroll Effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            const backToTop = document.getElementById('backToTop');
            
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                backToTop.classList.add('visible');
            } else {
                navbar.classList.remove('scrolled');
                backToTop.classList.remove('visible');
            }
        });

        // Back to Top Functionality
        document.getElementById('backToTop').addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Smooth Scrolling with Advanced Easing
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Advanced Scroll Reveal Animation
        function advancedReveal() {
            const reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
            
            reveals.forEach((element, index) => {
                const windowHeight = window.innerHeight;
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 100;
                
                if (elementTop < windowHeight - elementVisible) {
                    setTimeout(() => {
                        element.classList.add('active');
                    }, index * 100); // Stagger animation
                }
            });
        }

        // Advanced Counter Animation with Easing
        function animateCounters() {
            const counters = document.querySelectorAll('.stats-number[data-count]');
            
            counters.forEach((counter, index) => {
                const target = parseInt(counter.getAttribute('data-count'));
                const duration = 3000;
                let startTime = null;
                
                // Ease out cubic function
                const easeOutCubic = t => 1 - Math.pow(1 - t, 3);
                
                function animate(currentTime) {
                    if (startTime === null) startTime = currentTime;
                    const timeElapsed = currentTime - startTime;
                    const progress = Math.min(timeElapsed / duration, 1);
                    
                    const easedProgress = easeOutCubic(progress);
                    const current = Math.floor(easedProgress * target);
                    
                    if (target > 1000) {
                        counter.textContent = current.toLocaleString() + '+';
                    } else {
                        counter.textContent = current + (target < 100 ? '%' : '+');
                    }
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                }
                
                setTimeout(() => {
                    requestAnimationFrame(animate);
                }, index * 200);
            });
        }

        // Advanced Intersection Observer
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    if (entry.target.classList.contains('stats')) {
                        animateCounters();
                    }
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Advanced Parallax Effects
        function advancedParallax() {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            
            // Parallax for hero background
            const hero = document.querySelector('.hero::before');
            
            // Parallax for floating cards
            document.querySelectorAll('.floating-card').forEach((card, index) => {
                const speed = 0.3 + (index * 0.1);
                card.style.transform = `translateY(${scrolled * speed}px) ${card.style.transform || ''}`;
            });
            
            // Parallax for particles
            document.querySelectorAll('.particle').forEach((particle, index) => {
                const speed = 0.1 + (index % 3) * 0.05;
                particle.style.transform += ` translateX(${scrolled * speed}px)`;
            });
        }

        // Advanced Button Ripple Effect
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const ripple = document.createElement('span');
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple-effect');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Enhanced Feature Card Interactions
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                this.style.setProperty('--mouse-x', x + 'px');
                this.style.setProperty('--mouse-y', y + 'px');
            });
        });

        // Advanced Mobile Menu Handling
        document.querySelectorAll('.navbar-nav a').forEach(link => {
            link.addEventListener('click', () => {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            });
        });

        // Typewriter Effect for Hero Title
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            element.innerHTML = '';
            
            function type() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                } else {
                    element.classList.add('typing-complete');
                }
            }
            
            type();
        }

        // Advanced Mouse Tracking for Interactive Elements
        document.addEventListener('mousemove', (e) => {
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            // Move floating elements slightly based on mouse position
            document.querySelectorAll('.floating-card').forEach((card, index) => {
                const intensity = 20 + index * 5;
                const xMove = (mouseX - 0.5) * intensity;
                const yMove = (mouseY - 0.5) * intensity;
                
                card.style.transform += ` translate(${xMove}px, ${yMove}px)`;
            });
        });

        // Initialize Advanced Features
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            advancedReveal();
            
            // Initialize typing animation after a delay
            setTimeout(() => {
                const heroTitle = document.querySelector('.hero-title');
                if (heroTitle) {
                    const originalText = heroTitle.textContent;
                    typeWriter(heroTitle, originalText, 100);
                }
            }, 4000);
            
            // Observe elements
            document.querySelector('.stats') && observer.observe(document.querySelector('.stats'));
        });

        // Event Listeners
        window.addEventListener('scroll', () => {
            advancedReveal();
            advancedParallax();
        });

        // Performance optimized scroll handling
        let ticking = false;
        function updateOnScroll() {
            advancedReveal();
            advancedParallax();
            ticking = false;
        }

        window.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(updateOnScroll);
                ticking = true;
            }
        });

        // Add ripple effect styles
        const style = document.createElement('style');
        style.textContent = `
            .ripple-effect {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            }
            
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }

            .typing-complete {
                animation: textGlow 3s ease-in-out infinite;
            }
        `;
        document.head.appendChild(style);

        console.log('🚀 Advanced InternshipHub loaded successfully!');
    </script>
</body>
</html>
